<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the "CRM Data" sheet (36 columns).
 * Sorted old -> new date, only "Completed" status rows feed commission maths.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_data', function (Blueprint $table) {
            $table->id();
            $table->string('branch')->nullable();                   // A
            $table->string('applied_intake_date')->nullable();      // B  e.g. "April, 2025"
            $table->string('contact_id')->nullable()->index();      // C
            $table->string('sub_agent_name')->nullable();           // D
            $table->string('super_agent_name')->nullable();         // E
            $table->string('partner_name')->nullable()->index();    // F
            $table->string('client_id')->nullable()->index();       // G  <-- lookup key
            $table->string('name')->nullable();                     // H
            $table->string('product_name')->nullable();             // I
            $table->date('start_date')->nullable();                 // J
            $table->date('end_date')->nullable();                   // K
            $table->decimal('fee_total', 14, 2)->default(0);        // L
            $table->text('assignees')->nullable();                  // M
            $table->string('last_updated')->nullable();             // N
            $table->string('application_id')->nullable()->index();  // O
            $table->string('status')->nullable()->index();          // P  "Completed"
            $table->string('workflow')->nullable();                 // Q
            $table->string('deal_id')->nullable();                  // R
            $table->string('deal_name')->nullable();                // S
            $table->string('email')->nullable();                    // T
            $table->string('client_phone')->nullable();             // U
            $table->string('application_owner')->nullable();        // V
            $table->string('product_type')->nullable();             // W
            $table->string('added_date')->nullable();               // X
            $table->string('client_dob')->nullable();               // Y
            $table->string('client_visa_expiry_date')->nullable();  // Z
            $table->string('partner_branch')->nullable();           // AA
            $table->string('product_sub_type')->nullable();         // AB
            $table->string('intake_date')->nullable();              // AC "Apr 1, 2025"
            $table->string('current_stage')->nullable();            // AD
            $table->decimal('total_fee_discount', 14, 2)->default(0); // AE
            $table->text('discount_remarks')->nullable();           // AF
            $table->string('deal_id_alt')->nullable();              // AG
            $table->string('client_first_name')->nullable();        // AH
            $table->string('client_last_name')->nullable();         // AI
            $table->string('branch_super_sub')->nullable();         // AJ
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_data');
    }
};
