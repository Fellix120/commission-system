<?php

namespace App\Http\Controllers;

use App\Models\CrmData;
use App\Models\Student;
use App\Services\FormulaMapService;
use App\Services\StudentDetailService;
use Illuminate\Http\Request;

/**
 * The 'Student Details-Formula' tab.
 *
 * Shows the formula behind every Student Details column, and -- if a student
 * is picked -- resolves each one against that student's real CRM rows so an
 * operator can see exactly where a figure came from.
 */
class FormulaController extends Controller
{
    public function __construct(
        protected FormulaMapService $formulas,
        protected StudentDetailService $details,
    ) {
    }

    public function index(Request $request)
    {
        // Every student, resolved through the formulas, with the source row
        // count beside each one. This is the sheet -- the single-student trace
        // below is the drill-down.
        $rows = Student::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', $term)
                    ->orWhere('client_id_crm', 'like', $term)
                    ->orWhere('product_name', 'like', $term));
            })
            ->when($request->filled('partner'), fn ($q) => $q->where('partner_name', $request->partner))
            ->when($request->filled('branch'), fn ($q) => $q->where('branch', $request->branch))
            ->when($request->filled('intake'), fn ($q) => $q->where('applied_intake_date', $request->intake))
            ->when($request->filled('course'), fn ($q) => $q->where('product_name', 'like', '%'.$request->course.'%'))
            ->when(! $request->boolean('archived'), fn ($q) => $q->where('is_archived', false))
            ->when($request->boolean('archived'), fn ($q) => $q->where('is_archived', true))
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        // How many CRM rows sit behind each visible student, in one query
        // rather than one per row.
        $clientIds = $rows->pluck('client_id_crm')->filter();

        $rowCounts = CrmData::completed()
            ->whereIn('client_id', $clientIds)
            ->selectRaw('client_id, COUNT(*) as n')
            ->groupBy('client_id')
            ->pluck('n', 'client_id');

        // Filter options.
        $partners = Student::distinct()->orderBy('partner_name')->pluck('partner_name')->filter();
        $branches = Student::distinct()->orderBy('branch')->pluck('branch')->filter();
        $intakes  = Student::distinct()->orderBy('applied_intake_date')->pluck('applied_intake_date')->filter();
        $courses  = StudentDetailService::courseOptions();

        // Optional single-student trace.
        $student = null;
        $crmRows = collect();
        $trace = [];

        if ($clientId = $request->query('client_id')) {
            $student = Student::where('client_id_crm', $clientId)->first();
        }

        if ($student) {
            $crmRows = CrmData::completed()
                ->where('client_id', $student->client_id_crm)
                ->orderBy('start_date')
                ->get();

            $trace = $this->trace($student, $crmRows);
        }

        return view('formula.index', [
            'columns' => $this->formulas->columns(),
            'derived' => $this->formulas->derivedColumns(),
            'keyFormula' => FormulaMapService::KEY_FORMULA,
            'rows' => $rows,
            'rowCounts' => $rowCounts,
            'partners' => $partners,
            'branches' => $branches,
            'intakes' => $intakes,
            'courses' => $courses,
            'student' => $student,
            'crmRows' => $crmRows,
            'trace' => $trace,
        ]);
    }

    /**
     * Resolve each formula against one student's actual rows.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function trace(Student $student, $crmRows): array
    {
        $first = $crmRows->first();
        $n = $crmRows->count();

        $products = $crmRows->pluck('product_name')
            ->map(fn ($course) => trim((string) $course))
            ->filter()
            ->unique(fn ($course) => mb_strtolower($course))
            ->values();

        return [
            [
                'field' => 'Client ID',
                'excel' => "UNIQUE('CRM Data'!G:G)",
                'resolved' => "matched {$n} CRM row".($n === 1 ? '' : 's'),
                'value' => $student->client_id_crm,
                'flag' => $n === 0 ? 'danger' : null,
            ],
            [
                'field' => 'Partner Name',
                'excel' => "XLOOKUP(…,'CRM Data'!F:F)",
                'resolved' => 'first matching row',
                'value' => $first?->partner_name ?? '—',
                'flag' => null,
            ],
            [
                'field' => 'Product Name',
                'excel' => 'TEXTJOIN(" + ", TRUE, FILTER(…))',
                'resolved' => $products->count().' course'.($products->count() === 1 ? '' : 's').' joined',
                'value' => $products->implode(' + ') ?: '—',
                'flag' => null,
            ],
            [
                'field' => 'Start Date',
                'excel' => "MINIFS('CRM Data'!J:J,…)",
                'resolved' => 'earliest of '.$n,
                'value' => optional($student->start_date)->format('d/m/Y') ?? '—',
                'flag' => null,
            ],
            [
                'field' => 'End Date',
                'excel' => "MAXIFS('CRM Data'!K:K,…)",
                'resolved' => 'latest of '.$n,
                'value' => optional($student->end_date)->format('d/m/Y') ?? '—',
                'flag' => null,
            ],
            [
                'field' => 'Course Duration',
                'excel' => '(End − Start)/365',
                'resolved' => 'years',
                'value' => number_format((float) $student->course_duration, 6),
                'flag' => null,
            ],
            [
                'field' => 'Fee Total',
                'excel' => "SUMIFS('CRM Data'!L:L,…)",
                'resolved' => 'sum of '.$n.' row'.($n === 1 ? '' : 's'),
                'value' => number_format((float) $student->fee_total, 2),
                'flag' => $student->feeLooksUnderstated($n) ? 'warning' : null,
            ],
            [
                'field' => 'Credit Fee',
                'excel' => 'typed by hand',
                'resolved' => 'operator value, preserved on refresh',
                'value' => number_format((float) $student->credit_fee, 2),
                'flag' => 'manual',
            ],
            [
                'field' => 'Paid Fee',
                'excel' => 'from Invoice (SID match)',
                'resolved' => $student->invoices()->count().' invoice row(s)',
                'value' => number_format((float) $student->paid_fee, 2),
                'flag' => null,
            ],
            [
                'field' => 'Remaining Fee',
                'excel' => 'Credit − Paid + Adjustment',
                'resolved' => $student->isSettled() ? 'settled' : 'outstanding',
                'value' => number_format((float) $student->remaining_fee, 2),
                'flag' => $student->remaining_fee > 0 ? 'danger' : 'success',
            ],
        ];
    }

}
