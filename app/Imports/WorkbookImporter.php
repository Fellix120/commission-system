<?php

namespace App\Imports;

use App\Models\CrmData;
use App\Models\Import;
use App\Models\Invoice;
use App\Models\RawDataExport;
use App\Models\Student;
use App\Models\StudentIntakeCommission;
use App\Services\StudentDetailService;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Reads Templete_for_Commission_Working.xlsx.
 *
 * Only the three source tabs are read: Raw Data Export, CRM Data and Invoice.
 * "Student Details" is deliberately ignored -- it is a value paste that this
 * app rebuilds from CRM Data + Invoice, so importing it would mean trusting a
 * frozen copy over its own source. "Student Details-Formula" and "Summary" are
 * documentation and hold no data.
 *
 * Every run is logged to the imports table, including failures.
 */
class WorkbookImporter
{
    /** Sheets read, in the order they are processed. */
    public const SOURCE_SHEETS = ['Raw Data Export', 'CRM Data', 'Invoice'];

    /** Column order for each sheet, matching the workbook exactly. */
    protected const RAW_COLUMNS = [
        'added_date', 'application_id', 'deal_id', 'deal_name', 'contact_id',
        'client_first_name', 'client_last_name', 'client_phone', 'email', 'client_dob',
        'client_visa_expiry_date', 'branch', 'workflow', 'client_id', 'partner_name',
        'partner_branch', 'product_name', 'product_type', 'product_sub_type', 'intake_date',
        'start_date', 'end_date', 'current_stage', 'status', 'application_owner',
        'assignees', 'sub_agent_name', 'super_agent_name', 'fee_total', 'total_fee_discount',
        'discount_remarks', 'last_updated', 'applied_intake_date', 'deal_id_alt', 'branch_super_sub',
    ];

    protected const CRM_COLUMNS = [
        'branch', 'applied_intake_date', 'contact_id', 'sub_agent_name', 'super_agent_name',
        'partner_name', 'client_id', 'name', 'product_name', 'start_date',
        'end_date', 'fee_total', 'assignees', 'last_updated', 'application_id',
        'status', 'workflow', 'deal_id', 'deal_name', 'email',
        'client_phone', 'application_owner', 'product_type', 'added_date', 'client_dob',
        'client_visa_expiry_date', 'partner_branch', 'product_sub_type', 'intake_date', 'current_stage',
        'total_fee_discount', 'discount_remarks', 'deal_id_alt', 'client_first_name', 'client_last_name',
        'branch_super_sub',
    ];

    protected const INVOICE_COLUMNS = [
        'application_id', 'branch', 'subagent', 'provider_name', 'sid',
        'name', 'course', 'start_date', 'end_date', 'commission_intake',
        'total_fee', 'rate', 'commission', 'discount', 'net_commission',
        'royalty_rate', 'bonus', 'eevs_ho', 'branch_commission', 'branch_bonus',
        'remarks', 'po_number',
    ];

    /**
     * Real date columns, per sheet.
     *
     * These differ by table and the difference matters: added_date and
     * last_updated are date columns on raw_data_exports but plain strings on
     * crm_data, where they hold labels such as "Apr 1, 2025". Converting those
     * would overwrite the exported text with a guess, so each sheet gets its
     * own list, matching its migration exactly. Everything not listed here --
     * applied_intake_date ("July, 2025"), intake_date, client_dob -- stays a
     * string.
     */
    protected const DATE_COLUMNS = [
        'Raw Data Export' => ['added_date', 'start_date', 'end_date', 'last_updated'],
        'CRM Data'        => ['start_date', 'end_date'],
        'Invoice'         => ['start_date', 'end_date'],
    ];

    /** Columns holding numbers. */
    protected const NUMERIC_COLUMNS = [
        'fee_total', 'total_fee_discount', 'total_fee', 'rate', 'commission',
        'discount', 'net_commission', 'royalty_rate', 'bonus', 'eevs_ho',
        'branch_commission', 'branch_bonus',
    ];

    public function __construct(protected StudentDetailService $details)
    {
    }
    /**
     * Import a workbook and log the run.
     *
     * @param  string  $path      absolute path to the uploaded file
     * @param  string  $filename  original name, for the log
     * @param  bool    $replace   wipe the source tabs first
     */
    public function import(string $path, string $filename, bool $replace = false): Import
    {
        $started = microtime(true);

        $import = Import::create([
            'filename' => $filename,
            'file_size' => is_file($path) ? filesize($path) : 0,
            'status' => 'pending',
            'replaced_existing' => $replace,
        ]);

        try {
            $spreadsheet = IOFactory::createReaderForFile($path)
                ->setReadDataOnly(true)
                ->load($path);

            $found = [];
            $counts = ['raw' => 0, 'crm' => 0, 'invoice' => 0];

            DB::transaction(function () use ($spreadsheet, $replace, &$found, &$counts) {
                if ($replace) {
                    $this->wipe();
                }

                foreach (self::SOURCE_SHEETS as $name) {
                    if (! $spreadsheet->sheetNameExists($name)) {
                        continue;
                    }

                    $found[] = $name;
                    $rows = $spreadsheet->getSheetByName($name)->toArray(null, true, false, false);

                    $counts[match ($name) {
                        'Raw Data Export' => 'raw',
                        'CRM Data' => 'crm',
                        'Invoice' => 'invoice',
                    }] = match ($name) {
                        'Raw Data Export' => $this->load($rows, self::RAW_COLUMNS, RawDataExport::class, $name),
                        'CRM Data' => $this->load($rows, self::CRM_COLUMNS, CrmData::class, $name),
                        'Invoice' => $this->load($rows, self::INVOICE_COLUMNS, Invoice::class, $name),
                    };
                }
            });

            if (empty($found)) {
                throw new \RuntimeException(
                    'No recognised sheets. Expected at least one of: '.implode(', ', self::SOURCE_SHEETS).'.'
                );
            }

            // Rebuild Student Details from what was just loaded. Archived
            // students keep their frozen figures, so count them separately
            // rather than pretending the import touched them.
            $before = Student::archived()->count();
            $built = $this->details->refreshAll(false);

            // Cross-check the rebuild against the workbook's own value paste.
            $shortfall = $this->checkAgainstValuePaste($spreadsheet);

            $import->update([
                'status' => 'completed',
                'students_understated' => $shortfall['count'],
                'understated_note' => $shortfall['note'],
                'raw_rows' => $counts['raw'],
                'crm_rows' => $counts['crm'],
                'invoice_rows' => $counts['invoice'],
                'students_built' => $built,
                'students_skipped' => $before,
                'sheets_found' => implode(',', $found),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);
        }

        return $import->fresh();
    }

    /**
     * Compare the rebuilt figures against the "Student Details" value paste.
     *
     * That tab is never imported -- it is a frozen copy and CRM Data is the
     * source of truth. But it is worth reading, because if it claims a higher
     * Fee Total than CRM Data can account for, the export is missing course
     * rows. The sample workbook does exactly this: Student Details lists
     * "Diploma of Business + Bachelor of Business" and a fee of 114,960 for
     * client 12630942, while CRM Data carries only the Diploma at 24,960.
     *
     * Reporting it here means a short export shows up in the log rather than
     * quietly producing low commission figures.
     *
     * @return array{count:int, note:string|null}
     */
    protected function checkAgainstValuePaste($spreadsheet): array
    {
        if (! $spreadsheet->sheetNameExists('Student Details')) {
            return ['count' => 0, 'note' => null];
        }

        $rows = $spreadsheet->getSheetByName('Student Details')->toArray(null, true, false, false);
        array_shift($rows);

        $short = [];

        foreach ($rows as $row) {
            $clientId = $row[6] ?? null;      // G  Client ID-CRM
            $pastedName = (string) ($row[9] ?? '');    // J  Product Name
            $pastedFee = $row[13] ?? null;    // N  Fee Total

            if (! $clientId || ! is_numeric($pastedFee)) {
                continue;
            }

            $student = Student::where('client_id_crm', (string) $clientId)->first();

            if (! $student) {
                continue;
            }

            // A cent of rounding is not a shortfall; a missing course is.
            $gap = (float) $pastedFee - (float) $student->fee_total;

            if ($gap <= 1.0) {
                continue;
            }

            $short[] = [
                'client_id' => (string) $clientId,
                'name' => (string) ($row[8] ?? ''),
                'gap' => $gap,
                'missing' => $this->missingCourses($pastedName, $student->product_name),
            ];
        }

        if (empty($short)) {
            return ['count' => 0, 'note' => null];
        }

        return [
            'count' => count($short),
            'note' => $this->describeShortfall($short),
        ];
    }

    /**
     * Which courses the value paste names that the rebuild does not have.
     *
     * @return array<int, string>
     */
    protected function missingCourses(string $pastedName, ?string $builtName): array
    {
        $pasted = array_map('trim', explode(' + ', $pastedName));
        $built = array_map('trim', explode(' + ', (string) $builtName));

        // Count-aware: the paste may legitimately name the same course twice
        // (one per CRM row), so a single built row still leaves one missing.
        foreach ($built as $course) {
            $at = array_search($course, $pasted, true);

            if ($at !== false) {
                unset($pasted[$at]);
            }
        }

        return array_values(array_filter($pasted));
    }

    /**
     * A note an operator can act on: who, how much, and which course to chase.
     *
     * @param  array<int, array{client_id:string, name:string, gap:float, missing:array<int,string>}>  $short
     */
    protected function describeShortfall(array $short): string
    {
        $total = array_sum(array_column($short, 'gap'));

        $lines = [];

        foreach (array_slice($short, 0, 5) as $s) {
            $line = $s['client_id'].' '.$s['name'].' (short '.number_format($s['gap']).')';

            if ($s['missing']) {
                $line .= ' - missing: '.implode(', ', $s['missing']);
            }

            $lines[] = $line;
        }

        if (count($short) > 5) {
            $lines[] = 'and '.(count($short) - 5).' more';
        }

        return 'Student Details claims '.number_format($total).' more in fees than CRM Data supports, across '
            .count($short).' student(s). The CRM export is missing course rows, so commission is understated. '
            .implode('; ', $lines)
            .'. Re-export CRM Data with every course row per student, then import again.';
    }

    /**
     * Clear the source tabs and everything derived from them.
     */
    protected function wipe(): void
    {
        StudentIntakeCommission::query()->delete();
        Student::query()->delete();
        RawDataExport::query()->delete();
        CrmData::query()->delete();
        Invoice::query()->delete();
    }

    /**
     * Turn sheet rows into model rows.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, string>             $columns
     * @param  class-string                   $model
     * @param  string                         $sheet   picks the date-column list
     */
    protected function load(array $rows, array $columns, string $model, string $sheet): int
    {
        array_shift($rows);   // header

        $batch = [];
        $now = now();

        foreach ($rows as $row) {
            if ($this->isBlank($row)) {
                continue;
            }

            $record = [];

            foreach ($columns as $i => $column) {
                $record[$column] = $this->clean($row[$i] ?? null, $column, $sheet);
            }

            $record['created_at'] = $now;
            $record['updated_at'] = $now;

            $batch[] = $record;
        }

        // Chunked so a large export does not exceed the placeholder limit.
        foreach (array_chunk($batch, 200) as $chunk) {
            $model::insert($chunk);
        }

        return count($batch);
    }

    /** @param  array<int, mixed>  $row */
    protected function isBlank(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Normalise one cell.
     */
    protected function clean(mixed $value, string $column, string $sheet): mixed
    {
        $isBlank = $value === null || (is_string($value) && trim($value) === '');

        if ($isBlank && in_array($column, self::NUMERIC_COLUMNS, true)) {
            return 0.0;   // see above: these columns are NOT NULL DEFAULT 0
        }

        if ($isBlank) {
            return null;
        }

        if (in_array($column, self::DATE_COLUMNS[$sheet] ?? [], true)) {
            return $this->toDate($value);
        }

        if (in_array($column, self::NUMERIC_COLUMNS, true)) {
            // Money columns are NOT NULL DEFAULT 0 in the schema, and an
            // explicit null bypasses the default rather than falling back to
            // it. 12 of the 54 invoice rows in the sample workbook have a
            // blank total fee, so returning null here fails the whole import.
            // A blank money cell means nothing was paid, so it is zero.
            return is_numeric($value) ? (float) $value : 0.0;
        }

        return is_string($value) ? trim($value) : $value;
    }

    /**
     * Dates arrive either as an Excel serial (a float) or as text, depending on
     * how the tab was exported.
     */
    protected function toDate(mixed $value): ?string
    {
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return (new \DateTime(trim((string) $value)))->format('Y-m-d');
        } catch (\Throwable) {
            return null;   // e.g. "July, 2025" -- an intake label, not a date
        }
    }
}
