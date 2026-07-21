<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Requirement 3: archive old students whose commission is settled. */
class ArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_settled_student_can_be_archived(): void
    {
        $student = Student::create([
            'client_id_crm' => 'X1', 'name' => 'Settled Sam',
            'remaining_fee' => 0, 'remaining_bonus' => 0,
        ]);

        $this->assertTrue($student->isSettled());

        $this->post(route('students.archive', $student))->assertRedirect();

        $this->assertTrue($student->fresh()->is_archived);
        $this->assertNotNull($student->fresh()->archived_at);
    }

    public function test_student_with_balance_is_not_archived_without_force(): void
    {
        $student = Student::create([
            'client_id_crm' => 'X2', 'name' => 'Owing Olive',
            'remaining_fee' => 5000, 'remaining_bonus' => 0,
        ]);

        $this->assertFalse($student->isSettled());

        $this->post(route('students.archive', $student))->assertSessionHas('error');

        $this->assertFalse($student->fresh()->is_archived);
    }

    public function test_force_archives_a_student_with_a_balance(): void
    {
        $student = Student::create([
            'client_id_crm' => 'X3', 'name' => 'Forced Fred',
            'remaining_fee' => 5000, 'remaining_bonus' => 0,
        ]);

        $this->post(route('students.archive', $student), ['force' => 1]);

        $this->assertTrue($student->fresh()->is_archived);
    }

    public function test_archived_students_are_hidden_from_the_default_list(): void
    {
        Student::create(['client_id_crm' => 'A1', 'name' => 'Active Ann']);
        Student::create(['client_id_crm' => 'A2', 'name' => 'Gone Greg', 'is_archived' => true]);

        $this->assertSame(1, Student::active()->count());
        $this->assertSame(1, Student::archived()->count());
    }

    public function test_archived_student_can_be_restored(): void
    {
        $student = Student::create([
            'client_id_crm' => 'X4', 'name' => 'Back Bob', 'is_archived' => true, 'archived_at' => now(),
        ]);

        $this->post(route('students.unarchive', $student));

        $this->assertFalse($student->fresh()->is_archived);
        $this->assertNull($student->fresh()->archived_at);
    }
}
