<?php

namespace App\Http\Controllers;

use App\Models\CrmData;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentIntakeCommission;
use App\Services\StudentDetailService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display dashboard statistics using the same filters as Student Details.
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'partner' => trim((string) $request->input('partner', '')),
            'branch' => trim((string) $request->input('branch', '')),
            'intake' => trim((string) $request->input('intake', '')),
            'course' => trim((string) $request->input('course', '')),
        ];

        $activeStudentQuery = $this->applyStudentFilters(
            Student::query()->active(),
            $filters
        );

        $archivedStudentQuery = $this->applyStudentFilters(
            Student::query()->archived(),
            $filters
        );

        /*
        |--------------------------------------------------------------------------
        | IDs used to connect dashboard source tables
        |--------------------------------------------------------------------------
        */

        $filteredStudentIds = (clone $activeStudentQuery)
            ->pluck('students.id');

        $filteredCrmClientIds = (clone $activeStudentQuery)
            ->whereNotNull('client_id_crm')
            ->where('client_id_crm', '!=', '')
            ->pluck('client_id_crm')
            ->filter()
            ->unique()
            ->values();

        $filteredInvoiceClientIds = (clone $activeStudentQuery)
            ->whereNotNull('client_id_invoice')
            ->where('client_id_invoice', '!=', '')
            ->pluck('client_id_invoice')
            ->filter()
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Commission received by intake
        |--------------------------------------------------------------------------
        */

        $intakeColumns = StudentDetailService::intakeColumns();

        $intakeTotals = collect($intakeColumns)
            ->mapWithKeys(function ($code) use ($filteredStudentIds, $filters) {
                $query = StudentIntakeCommission::query()
                    ->where('intake_code', $code);

                if ($this->hasStudentFilter($filters)) {
                    if ($filteredStudentIds->isEmpty()) {
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->whereIn('student_id', $filteredStudentIds);
                    }
                }

                return [
                    $code => (float) $query->sum('amount_received'),
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | CRM and invoice rows matching filtered students
        |--------------------------------------------------------------------------
        */

        $crmQuery = CrmData::query()->completed();

        if ($this->hasStudentFilter($filters)) {
            if ($filteredCrmClientIds->isEmpty()) {
                $crmQuery->whereRaw('1 = 0');
            } else {
                $crmQuery->whereIn('client_id', $filteredCrmClientIds);
            }
        }

        $invoiceQuery = Invoice::query();

        if ($this->hasStudentFilter($filters)) {
            if ($filteredInvoiceClientIds->isEmpty()) {
                $invoiceQuery->whereRaw('1 = 0');
            } else {
                $invoiceQuery->whereIn('sid', $filteredInvoiceClientIds);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Dashboard cards
        |--------------------------------------------------------------------------
        */

        $stats = [
            'active_students' => (clone $activeStudentQuery)->count(),
            'archived_students' => (clone $archivedStudentQuery)->count(),
            'crm_completed' => (clone $crmQuery)->count(),
            'invoice_rows' => (clone $invoiceQuery)->count(),
            'total_credit_fee' => (float) (clone $activeStudentQuery)->sum('credit_fee'),
            'total_paid_fee' => (float) (clone $activeStudentQuery)->sum('paid_fee'),
            'total_remaining' => (float) (clone $activeStudentQuery)->sum('remaining_fee'),
            'net_commission' => (float) (clone $invoiceQuery)->sum('net_commission'),
            'branch_commission' => (float) (clone $invoiceQuery)->sum('branch_commission'),
            'last_refresh' => Student::max('values_refreshed_at'),
        ];

        /*
        |--------------------------------------------------------------------------
        | Top partners
        |--------------------------------------------------------------------------
        */

        $byPartner = (clone $activeStudentQuery)
            ->selectRaw('
                partner_name,
                COUNT(*) AS students,
                COALESCE(SUM(credit_fee), 0) AS credit_fee,
                COALESCE(SUM(paid_fee), 0) AS paid,
                COALESCE(SUM(remaining_fee), 0) AS remaining
            ')
            ->whereNotNull('partner_name')
            ->where('partner_name', '!=', '')
            ->groupBy('partner_name')
            ->orderByDesc('paid')
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Searchable filter options
        |--------------------------------------------------------------------------
        */

        $partners = Student::query()
            ->whereNotNull('partner_name')
            ->where('partner_name', '!=', '')
            ->orderBy('partner_name')
            ->pluck('partner_name')
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique(fn($value) => mb_strtolower($value))
            ->values();

        $branches = Student::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->orderBy('branch')
            ->pluck('branch')
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique(fn($value) => mb_strtolower($value))
            ->values();

        // Same source and same value as StudentController::index().
        $intakes = Student::query()
            ->whereNotNull('applied_intake_date')
            ->where('applied_intake_date', '!=', '')
            ->orderBy('applied_intake_date')
            ->pluck('applied_intake_date')
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique(fn($value) => mb_strtolower($value))
            ->values();

        $courses = collect(StudentDetailService::courseOptions())
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique(fn($value) => mb_strtolower($value))
            ->sort()
            ->values();

        return view('dashboard', compact(
            'stats',
            'intakeTotals',
            'byPartner',
            'filters',
            'partners',
            'branches',
            'intakes',
            'courses'
        ));
    }

    /**
     * Apply the same filtering rules used by StudentController::index().
     */
    private function applyStudentFilters(Builder $query, array $filters): Builder
    {
        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';

            $query->where(function (Builder $studentQuery) use ($term) {
                $studentQuery
                    ->where('name', 'like', $term)
                    ->orWhere('client_id_crm', 'like', $term)
                    ->orWhere('client_id_invoice', 'like', $term)
                    ->orWhere('contact_id', 'like', $term)
                    ->orWhere('product_name', 'like', $term);
            });
        }

        if ($filters['partner'] !== '') {
            $query->where('partner_name', $filters['partner']);
        }

        if ($filters['branch'] !== '') {
            $query->where('branch', $filters['branch']);
        }

        if ($filters['intake'] !== '') {
            $query->where('applied_intake_date', $filters['intake']);
        }

        if ($filters['course'] !== '') {
            $query->where('product_name', 'like', '%' . $filters['course'] . '%');
        }

        return $query;
    }

    private function hasStudentFilter(array $filters): bool
    {
        return collect($filters)->contains(
            fn($value) => trim((string) $value) !== ''
        );
    }
}
