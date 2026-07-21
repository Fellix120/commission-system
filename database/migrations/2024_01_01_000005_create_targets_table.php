<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Requirement 4: uni/college-provided targets vs actual enrolment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            // Lengths are bounded because these three form a composite unique
            // index. Three unbounded VARCHAR(255) columns come to 3060 bytes in
            // utf8mb4 -- under InnoDB's 3072 limit by only 12 bytes, and well
            // over the 767-byte limit on older MySQL 5.7. These hold partner
            // names and intake codes, so the shorter lengths are honest anyway.
            $table->string('partner_name', 191)->index();   // matches students.partner_name
            $table->string('intake_code', 64)->index();     // "T2-2025" / "July, 2025"
            $table->string('branch', 100)->nullable();
            $table->unsignedInteger('target_enrolment')->default(0);
            $table->decimal('target_commission', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['partner_name', 'intake_code', 'branch'], 'targets_unique_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
