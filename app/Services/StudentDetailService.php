<?php

namespace App\Services;

use App\Models\CrmData;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Port of the "Student Details-Formula" sheet.
 *
 * Excel column -> method mapping
 * -----------------------------------------------------------------
 *  A-F, H  XLOOKUP($G2,'CRM Data'!$G:$G, ...)   -> lookupFirstCrmRow()
 *  J       TEXTJOIN of every Product Name       -> joinedProductNames()
 *  K       MINIFS('CRM Data'!J:J, G:G, key)     -> min(start_date)
 *  L       MAXIFS('CRM Data'!K:K, G:G, key)     -> max(end_date)
 *  M       (End - Start)/365                    -> courseDuration()
 *  N       SUMIFS('CRM Data'!L:L, G:G, key)     -> sum(fee_total)
 *  O       Credit Fee (commissionable / CR fee) -> creditFee()
 *  Q       Paid Fee   = SUM(Invoice.total_fee)  -> from invoices
 *  R       Paid Bonus = SUM(Invoice.bonus)
 *  S       Remaining Fee   = Credit - Paid + Adj
 *  T       Remaining Bonus = Bonus Due - Paid Bonus
 *  Y..AA   Per-intake received (T2-2025, ...)   -> intakeBreakdown()
 *
 * Every value is written to `students` as a VALUE PASTE, exactly as the
 * Summary tab requires: figures already reported must not drift when the
 * CRM is re-exported next month.
 */
class StudentDetailService
{
    /**
     * Rebuild the Student Details display sheet from CRM Data + Invoice.
     * Archived students are skipped so settled figures stay frozen.
     */
    public function refreshAll(bool $includeArchived = false): int
    {
        $clientIds = CrmData::completed()
            ->whereNotNull('client_id')
            ->where('client_id', '!=', '')
            ->orderBy('start_date')          // "old date to new date" ordering
            ->pluck('client_id')
            ->unique()
            ->values();

        $count = 0;

        DB::transaction(function () use ($clientIds, $includeArchived, &$count) {
            foreach ($clientIds as $clientId) {
                $existing = Student::where('client_id_crm', $clientId)->first();

                if ($existing && $existing->is_archived && !$includeArchived) {
                    continue;   // settled: leave the value-pasted figures alone
                }

                $this->refreshOne((string) $clientId);
                $count++;
            }
        });

        return $count;
    }

    public function refreshOne(string $clientId): ?Student
    {
        $rows = CrmData::completed()
            ->where('client_id', $clientId)
            ->orderBy('start_date')
            ->get();

        if ($rows->isEmpty()) {
            // Nothing left to build from -- the last CRM row for this client
            // was deleted. Drop the stale student row rather than leaving a
            // value paste behind that no source supports, and rather than
            // throwing, since deleting a CRM row is a normal operator action.
            Student::where('client_id_crm', $clientId)->each(function ($student) {
                $student->intakeCommissions()->delete();
                $student->delete();
            });

            return null;
        }

        $first = $rows->first();                       // XLOOKUP takes the first match

        $startDate = $rows->whereNotNull('start_date')->min('start_date');  // MINIFS
        $endDate = $rows->whereNotNull('end_date')->max('end_date');      // MAXIFS
        $feeTotal = (float) $rows->sum('fee_total');                       // SUMIFS

        $duration = $this->courseDuration($startDate, $endDate);

        $invoices = Invoice::where('sid', $clientId)->get();

        $paidFee = (float) $invoices->sum('total_fee');
        $paidBonus = (float) $invoices->sum('bonus');

        $student = Student::firstOrNew(['client_id_crm' => $clientId]);

        // Credit Fee (CR fee) is the commissionable base. Some partners only
        // pay on year 1, so it is an operator-owned field: keep any manual
        // override, otherwise seed it from the full fee.
        $creditFee = $student->exists && $student->credit_fee !== null
            ? (float) $student->credit_fee
            : $this->creditFee($feeTotal, $duration, $first->partner_name);

        $bonusDue = $student->exists ? $student->bonus_due : null;
        $feeAdj = $student->exists ? (float) $student->fee_adjustment : 0.0;
        $bonusAdj = $student->exists ? (float) $student->bonus_adjustment : 0.0;

        $student->fill([
            'branch' => $first->branch,
            'applied_intake_date' => $first->applied_intake_date,
            'contact_id' => $first->contact_id,
            'sub_agent_name' => $first->sub_agent_name,
            'super_agent_name' => $first->super_agent_name,
            'partner_name' => $first->partner_name,
            'client_id_invoice' => $clientId,
            'name' => $first->name,
            'product_name' => $this->joinedProductNames($rows),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'course_duration' => $duration,
            'fee_total' => $feeTotal,
            'credit_fee' => $creditFee,
            'paid_fee' => $paidFee,
            'paid_bonus' => $paidBonus,
            'remaining_fee' => $this->remainingFee($creditFee, $paidFee, $feeAdj),
            'remaining_bonus' => $this->remainingBonus($bonusDue, $paidBonus, $bonusAdj),
            'values_refreshed_at' => now(),
        ]);

        $student->save();

        $this->syncIntakeCommissions($student, $invoices);

        return $student;
    }

    /** Excel M: (End Date - Start Date) / 365, in years. */
    public function courseDuration($startDate, $endDate): float
    {
        if (!$startDate || !$endDate) {
            return 0.0;
        }

        $start = $startDate instanceof \DateTimeInterface ? $startDate : new \DateTime($startDate);
        $end = $endDate instanceof \DateTimeInterface ? $endDate : new \DateTime($endDate);

        $days = (int) $start->diff($end)->format('%r%a');

        return round($days / 365, 6);
    }

    /** Excel J: join each distinct, non-empty product name once. */
    public function joinedProductNames($rows): string
    {
        return $rows->pluck('product_name')
            ->map(fn($course) => trim((string) $course))
            ->filter()
            ->unique(fn($course) => mb_strtolower($course))
            ->values()
            ->implode(' + ');
    }

    /**
     * Build clean course options from Student.product_name.
     *
     * A student may have multiple different courses stored as
     * "Course A + Course B". The filter must list each course separately and
     * must never display duplicate options caused by repeated CRM rows.
     */
    public static function courseOptions()
    {
        return Student::query()
            ->whereNotNull('product_name')
            ->pluck('product_name')
            ->flatMap(function ($value) {
                return preg_split('/\s+\+\s+/u', (string) $value) ?: [];
            })
            ->map(fn($course) => trim((string) $course))
            ->filter()
            ->unique(fn($course) => mb_strtolower($course))
            ->sort(fn($a, $b) => strcasecmp($a, $b))
            ->values();
    }

    /**
     * Excel O - "CR fee is the fee on which we receive the commission
     * (Some college or university allow commission only on 1st year fee)".
     *
     * Default: full fee for courses of one year or less; for longer courses
     * the pro-rata first-year slice. This is a starting estimate only --
     * the real rule is per-partner contract, so the field stays editable
     * and any manual value is preserved across refreshes.
     */
    public function creditFee(float $feeTotal, float $durationYears, ?string $partnerName = null): float
    {
        if ($durationYears <= 0) {
            return round($feeTotal, 2);
        }

        if ($durationYears <= 1.0) {
            return round($feeTotal, 2);
        }

        return round($feeTotal / $durationYears, 2);
    }

    /** Excel S: Credit Fee - Paid Fee + Fee Adjustment. */
    public function remainingFee(float $creditFee, float $paidFee, float $adjustment = 0.0): float
    {
        return round(max($creditFee - $paidFee + $adjustment, 0), 2);
    }

    /** Excel T: Bonus Due - Paid Bonus + Bonus Adjustment. */
    public function remainingBonus($bonusDue, float $paidBonus, float $adjustment = 0.0): float
    {
        return round(max(((float) $bonusDue) - $paidBonus + $adjustment, 0), 2);
    }

    /**
     * Requirement 2: Excel columns Y/Z/AA (T2-2025, T3-2025, T1-2026).
     * Driven by Invoice."Commission intake", so a new trimester needs no
     * schema change -- it simply appears.
     */
    public function syncIntakeBreakdown(Student $student): void
    {
        $this->syncIntakeCommissions($student, Invoice::where('sid', $student->client_id_invoice)->get());
    }

    protected function syncIntakeCommissions(Student $student, $invoices): void
    {
        $byIntake = $invoices
            ->filter(fn($i) => filled($i->commission_intake))
            ->groupBy('commission_intake')
            ->map(fn($group) => (float) $group->sum('total_fee'));

        foreach ($byIntake as $intakeCode => $amount) {
            $student->intakeCommissions()->updateOrCreate(
                ['intake_code' => $intakeCode],
                ['amount_received' => $amount]
            );
        }

        // Drop intakes that no longer have any invoice behind them.
        $student->intakeCommissions()
            ->whereNotIn('intake_code', $byIntake->keys()->all())
            ->delete();
    }

    /** Distinct intake codes present, newest-looking last (T2-2025, T3-2025, T1-2026). */
    public static function intakeColumns(): array
    {
        $codes = DB::table('student_intake_commissions')
            ->distinct()
            ->pluck('intake_code')
            ->all();

        usort($codes, function ($a, $b) {
            preg_match('/(\d{4})/', $a, $ya);
            preg_match('/(\d{4})/', $b, $yb);
            $yearA = $ya[1] ?? 0;
            $yearB = $yb[1] ?? 0;

            return $yearA === $yearB ? strcmp($a, $b) : $yearA <=> $yearB;
        });

        return $codes;
    }
}
