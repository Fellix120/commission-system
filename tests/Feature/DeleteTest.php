<?php

namespace Tests\Feature;

use App\Models\CrmData;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentIntakeCommission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_student_removes_its_intake_rows(): void
    {
        $student = Student::create(['client_id_crm' => 'D1', 'name' => 'Delete Dan']);
        $student->intakeCommissions()->create(['intake_code' => 'T2-2025', 'amount_received' => 6600]);

        $this->delete(route('students.destroy', $student))->assertRedirect();

        $this->assertModelMissing($student);
        $this->assertSame(0, StudentIntakeCommission::where('student_id', $student->id)->count());
    }

    public function test_bulk_delete_removes_every_selected_student(): void
    {
        $a = Student::create(['client_id_crm' => 'D2', 'name' => 'Ann']);
        $b = Student::create(['client_id_crm' => 'D3', 'name' => 'Bob']);
        $c = Student::create(['client_id_crm' => 'D4', 'name' => 'Cal']);

        $this->post(route('students.bulk-delete'), ['student_ids' => [$a->id, $b->id]]);

        $this->assertModelMissing($a);
        $this->assertModelMissing($b);
        $this->assertModelExists($c);
    }

    public function test_bulk_delete_rejects_an_empty_selection(): void
    {
        $this->post(route('students.bulk-delete'), ['student_ids' => []])
            ->assertSessionHasErrors('student_ids');
    }

    /**
     * Deleting the last CRM row for a client leaves nothing to rebuild from,
     * so the stale student row goes too rather than the refresh throwing.
     */
    public function test_deleting_the_last_crm_row_removes_the_student(): void
    {
        $row = CrmData::create([
            'client_id' => 'D5', 'name' => 'Solo Sam', 'status' => 'Completed',
            'fee_total' => 1000, 'start_date' => '2025-01-01', 'end_date' => '2025-06-01',
        ]);
        Student::create(['client_id_crm' => 'D5', 'name' => 'Solo Sam']);

        $this->delete(route('crm.destroy', $row))->assertRedirect();

        $this->assertSame(0, Student::where('client_id_crm', 'D5')->count());
    }

    public function test_deleting_an_invoice_refreshes_the_students_paid_fee(): void
    {
        CrmData::create([
            'client_id' => 'D6', 'name' => 'Inv Ivy', 'status' => 'Completed',
            'fee_total' => 5000, 'start_date' => '2025-01-01', 'end_date' => '2025-06-01',
        ]);
        $invoice = Invoice::create([
            'sid' => 'D6', 'name' => 'Inv Ivy', 'commission_intake' => 'T2-2025', 'total_fee' => 2000,
        ]);

        $this->delete(route('invoices.destroy', $invoice))->assertRedirect();

        $this->assertModelMissing($invoice);
        $this->assertSame(0.0, (float) Student::where('client_id_crm', 'D6')->first()->paid_fee);
    }
}
