<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the "Invoice" sheet (22 columns) - report from Head Office & college.
 * "Commission intake" (J) is what drives the per-intake columns T2-2025 etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('application_id')->nullable()->index();     // A
            $table->string('branch')->nullable();                      // B
            $table->string('subagent')->nullable();                    // C
            $table->string('provider_name')->nullable();               // D Super Agent
            $table->string('sid')->nullable()->index();                // E  <-- Client ID-Invoice
            $table->string('name')->nullable();                        // F
            $table->string('course')->nullable();                      // G
            $table->date('start_date')->nullable();                    // H
            $table->date('end_date')->nullable();                      // I
            $table->string('commission_intake')->nullable()->index();  // J "T2-2025"
            $table->decimal('total_fee', 14, 2)->default(0);           // K paid fee basis
            $table->decimal('rate', 8, 4)->default(0);                 // L 0.15 = 15%
            $table->decimal('commission', 14, 2)->default(0);          // M
            $table->decimal('discount', 14, 2)->default(0);            // N
            $table->decimal('net_commission', 14, 2)->default(0);      // O
            $table->decimal('royalty_rate', 8, 4)->default(0);         // P
            $table->decimal('bonus', 14, 2)->nullable();               // Q
            $table->decimal('eevs_ho', 14, 2)->default(0);             // R
            $table->decimal('branch_commission', 14, 2)->default(0);   // S
            $table->decimal('branch_bonus', 14, 2)->default(0);        // T
            $table->text('remarks')->nullable();                       // U
            $table->string('po_number')->nullable();                   // V
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
