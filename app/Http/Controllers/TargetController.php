<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Target;
use App\Services\StudentDetailService;
use App\Services\TargetComparisonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TargetController extends Controller
{
    public function __construct(
        protected TargetComparisonService $service
    ) {
    }

    /**
     * Display targets and compare them with filtered student enrolments.
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'partner' => trim((string) $request->input('partner', '')),
            'branch' => trim((string) $request->input('branch', '')),
            'intake' => trim((string) $request->input('intake', '')),
            'course' => trim((string) $request->input('course', '')),
        ];

        /*
        |--------------------------------------------------------------------------
        | Filter target rows
        |--------------------------------------------------------------------------
        |
        | Partner, branch and intake filter the target records themselves.
        | Search can find a target by partner, intake or branch.
        | Course is not stored on targets, so it filters actual students only.
        |
        */

        $targets = Target::query()
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = '%' . $filters['search'] . '%';

                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('partner_name', 'like', $search)
                        ->orWhere('intake_code', 'like', $search)
                        ->orWhere('branch', 'like', $search);
                });
            })
            ->when(
                $filters['partner'] !== '',
                fn($query) => $query->where('partner_name', $filters['partner'])
            )
            ->when(
                $filters['branch'] !== '',
                function ($query) use ($filters) {
                    $query->where(function ($branchQuery) use ($filters) {
                        $branchQuery
                            ->where('branch', $filters['branch'])
                            ->orWhereNull('branch')
                            ->orWhere('branch', '');
                    });
                }
            )
            ->when(
                $filters['intake'] !== '',
                fn($query) => $query->where('intake_code', $filters['intake'])
            )
            ->orderBy('partner_name')
            ->orderBy('intake_code')
            ->orderBy('branch')
            ->get();

        $comparison = $this->service->compare($targets, $filters);

        /*
        |--------------------------------------------------------------------------
        | Filter dropdown options
        |--------------------------------------------------------------------------
        */

        $partners = Student::query()
            ->whereNotNull('partner_name')
            ->where('partner_name', '!=', '')
            ->distinct()
            ->orderBy('partner_name')
            ->pluck('partner_name')
            ->filter()
            ->values();

        $branches = Student::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->filter()
            ->values();

        $intakes = Student::query()
            ->whereNotNull('applied_intake_date')
            ->where('applied_intake_date', '!=', '')
            ->distinct()
            ->orderBy('applied_intake_date')
            ->pluck('applied_intake_date')
            ->filter()
            ->values();

        $courses = StudentDetailService::courseOptions();

        return view('targets.index', compact(
            'comparison',
            'partners',
            'branches',
            'intakes',
            'courses',
            'filters'
        ));
    }

    /**
     * Create a new target or update an existing target.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'partner_name' => ['required', 'string', 'max:255'],
            'intake_code' => ['required', 'string', 'max:100'],
            'branch' => ['nullable', 'string', 'max:255'],
            'target_enrolment' => ['required', 'integer', 'min:0'],
            'target_commission' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['partner_name'] = trim($data['partner_name']);
        $data['intake_code'] = trim($data['intake_code']);
        $data['branch'] = isset($data['branch']) && trim($data['branch']) !== ''
            ? trim($data['branch'])
            : null;

        Target::updateOrCreate(
            [
                'partner_name' => $data['partner_name'],
                'intake_code' => $data['intake_code'],
                'branch' => $data['branch'],
            ],
            [
                'target_enrolment' => $data['target_enrolment'],
                'target_commission' => $data['target_commission'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return redirect()
            ->route('targets.index', $request->only([
                'search',
                'partner',
                'branch',
                'intake',
                'course',
            ]))
            ->with('status', 'Target saved.');
    }

    /**
     * Delete a target.
     */
    public function destroy(Target $target): RedirectResponse
    {
        $target->delete();

        return back()->with('status', 'Target removed.');
    }
}