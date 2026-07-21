<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Requirement 2: student-wise intake commission received (T2-2025, T3-2025, T1-2026...).
 *
 * The Excel sheet hardcodes one column per intake, which means a schema edit
 * every trimester. Here each intake is a row, so new intakes appear on their
 * own as soon as an Invoice carries a new "Commission intake" value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_intake_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('intake_code', 64)->index();          // "T2-2025"
            $table->decimal('amount_received', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['student_id', 'intake_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_intake_commissions');
    }
};
