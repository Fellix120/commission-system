<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\StudentDetailService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(protected StudentDetailService $service)
    {
    }

    /** Requirement 1: display as per Student Details sheet. */
    public function index(Request $request)
    {
        $intakeColumns = StudentDetailService::intakeColumns();

        $students = Student::query()
            ->with('intakeCommissions')
            ->when($request->boolean('archived'), fn ($q) => $q->archived(), fn ($q) => $q->active())
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', $term)
                    ->orWhere('client_id_crm', 'like', $term)
                    ->orWhere('contact_id', 'like', $term)
                    ->orWhere('product_name', 'like', $term));
            })
            ->when($request->filled('partner'), fn ($q) => $q->where('partner_name', $request->partner))
            ->when($request->filled('branch'), fn ($q) => $q->where('branch', $request->branch))
            ->when($request->filled('intake'), fn ($q) => $q->where('applied_intake_date', $request->intake))
            ->when($request->filled('course'), fn ($q) => $q->where('product_name', 'like', '%'.$request->course.'%'))
            ->orderBy('start_date')      // old -> new, per the Summary tab
            ->paginate(50)
            ->withQueryString();

        $partners = Student::distinct()->orderBy('partner_name')->pluck('partner_name')->filter();
        $branches = Student::distinct()->orderBy('branch')->pluck('branch')->filter();
        $intakes  = Student::distinct()->orderBy('applied_intake_date')->pluck('applied_intake_date')->filter();
        $courses  = StudentDetailService::courseOptions();

        return view('students.index', compact(
            'students', 'intakeColumns', 'partners', 'branches', 'intakes', 'courses'
        ));
    }

    public function show(Student $student)
    {
        $student->load('intakeCommissions', 'invoices', 'crmRows');

        return view('students.show', compact('student'));
    }

    /** Editable operator fields (CR fee, bonus due, adjustments, remarks). */
    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'credit_fee'        => ['nullable', 'numeric', 'min:0'],
            'bonus_due'         => ['nullable', 'numeric', 'min:0'],
            'fee_adjustment'    => ['nullable', 'numeric'],
            'bonus_adjustment'  => ['nullable', 'numeric'],
            'ho_remarks'        => ['nullable', 'string', 'max:2000'],
            'branch_remarks'    => ['nullable', 'string', 'max:2000'],
        ]);

        $student->fill($data);

        $student->remaining_fee = $this->service->remainingFee(
            (float) $student->credit_fee,
            (float) $student->paid_fee,
            (float) $student->fee_adjustment
        );

        $student->remaining_bonus = $this->service->remainingBonus(
            $student->bonus_due,
            (float) $student->paid_bonus,
            (float) $student->bonus_adjustment
        );

        $student->save();

        return back()->with('status', 'Student updated.');
    }

    /**
     * Explicit value-paste refresh. Nothing recalculates on its own, so a
     * figure already reported to Head Office cannot move underneath you.
     */
    // public function refresh(Request $request)
    // {
    //     $count = $this->service->refreshAll($request->boolean('include_archived'));

    //     return back()->with('status', "Refreshed {$count} student rows from CRM Data + Invoice.");
    // }

    public function refresh(Request $request)
    {
        $count = $this->service->refreshAll(
            $request->boolean('include_archived')
        );

        return redirect()
            ->back()
            ->with(
                'status',
                "Refreshed {$count} student rows from CRM Data + Invoice."
            );
    }

    /** Requirement 3: archive settled students. */
    public function archive(Request $request, Student $student)
    {
        if (! $student->isSettled() && ! $request->boolean('force')) {
            return back()->with('error',
                'Student still has outstanding fee or bonus. Tick "archive anyway" to override.');
        }

        $student->archive($request->input('archive_note'), $request->input('archived_by', 'operator'));

        return back()->with('status', "{$student->name} archived.");
    }

    public function unarchive(Student $student)
    {
        $student->unarchive();

        return back()->with('status', "{$student->name} restored to active.");
    }

    /**
     * Delete a student row.
     *
     * The row is a value paste rebuilt from CRM Data, so deleting it only
     * clears the reported figures -- a later refresh rebuilds it from source.
     * To remove a student for good, delete their CRM rows too.
     */
    public function destroy(Student $student)
    {
        $name = $student->name;

        // Intake commissions cascade via the FK; be explicit anyway so the
        // behaviour does not depend on the database engine.
        $student->intakeCommissions()->delete();
        $student->delete();

        return redirect()
            ->route('students.index')
            ->with('status', "Deleted {$name}. A refresh will rebuild the row from CRM Data.");
    }

    /**
     * Delete several students at once.
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->validate([
            'student_ids'   => ['required', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ])['student_ids'];

        $students = Student::whereIn('id', $ids)->get();

        foreach ($students as $student) {
            $student->intakeCommissions()->delete();
            $student->delete();
        }

        $n = $students->count();

        return back()->with('status', "Deleted {$n} student".($n === 1 ? '' : 's').'.');
    }

    public function bulkArchive(Request $request)
    {
        $ids = $request->validate([
            'student_ids'   => ['required', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ])['student_ids'];

        $archived = 0;

        foreach (Student::whereIn('id', $ids)->get() as $student) {
            if ($student->isSettled() || $request->boolean('force')) {
                $student->archive('Bulk archive', $request->input('archived_by', 'operator'));
                $archived++;
            }
        }

        return back()->with('status', "{$archived} student(s) archived.");
    }
}
