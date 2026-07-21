<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors "Student Details" - the VALUE-PASTE display sheet (27 columns).
 *
 * Requirement from Summary tab: values are frozen so monthly/quarterly
 * updates never silently move an already-reported figure. Recalculating
 * from "Student Details-Formula" is an explicit user action (Refresh),
 * mirroring an Excel value-paste.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('branch')->nullable();                    // A
            $table->string('applied_intake_date')->nullable();       // B
            $table->string('contact_id')->nullable()->index();       // C
            $table->string('sub_agent_name')->nullable();            // D
            $table->string('super_agent_name')->nullable();          // E
            $table->string('partner_name')->nullable()->index();     // F
            $table->string('client_id_crm')->nullable()->index();    // G lookup key
            $table->string('client_id_invoice')->nullable()->index();// H
            $table->string('name')->nullable();                      // I
            $table->text('product_name')->nullable();                // J TEXTJOIN of courses
            $table->date('start_date')->nullable();                  // K MINIFS
            $table->date('end_date')->nullable();                    // L MAXIFS
            $table->decimal('course_duration', 10, 6)->default(0);   // M (End-Start)/365
            $table->decimal('fee_total', 14, 2)->default(0);         // N SUMIFS
            $table->decimal('credit_fee', 14, 2)->default(0);        // O CR fee: commissionable
            $table->decimal('bonus_due', 14, 2)->nullable();         // P
            $table->decimal('paid_fee', 14, 2)->default(0);          // Q from Invoice
            $table->decimal('paid_bonus', 14, 2)->default(0);        // R
            $table->decimal('remaining_fee', 14, 2)->default(0);     // S
            $table->decimal('remaining_bonus', 14, 2)->default(0);   // T
            $table->decimal('fee_adjustment', 14, 2)->nullable();    // U
            $table->decimal('bonus_adjustment', 14, 2)->nullable();  // V
            $table->text('ho_remarks')->nullable();                  // W
            $table->text('branch_remarks')->nullable();              // X
            // Y, Z, AA (T2-2025 / T3-2025 / T1-2026) live in student_intake_commissions

            // Requirement 3: archive settled students
            $table->boolean('is_archived')->default(false)->index();
            $table->timestamp('archived_at')->nullable();
            $table->string('archived_by')->nullable();
            $table->text('archive_note')->nullable();

            $table->timestamp('values_refreshed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
