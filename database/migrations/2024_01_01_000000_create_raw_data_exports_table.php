<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors "Raw Data Export" sheet (35 columns) - untouched CRM dump.
 * Stage 1 only consumes Admission enrolments; OSHC lands in stage 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_data_exports', function (Blueprint $table) {
            $table->id();
            $table->date('added_date')->nullable();
            $table->string('application_id')->nullable()->index();
            $table->string('deal_id')->nullable();
            $table->string('deal_name')->nullable();
            $table->string('contact_id')->nullable();
            $table->string('client_first_name')->nullable();
            $table->string('client_last_name')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('client_dob')->nullable();
            $table->string('client_visa_expiry_date')->nullable();
            $table->string('branch')->nullable();
            $table->string('workflow')->nullable();
            $table->string('client_id')->nullable()->index();
            $table->string('partner_name')->nullable();
            $table->string('partner_branch')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_type')->nullable();
            $table->string('product_sub_type')->nullable();
            $table->string('intake_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('current_stage')->nullable();
            $table->string('status')->nullable()->index();
            $table->string('application_owner')->nullable();
            $table->text('assignees')->nullable();
            $table->string('sub_agent_name')->nullable();
            $table->string('super_agent_name')->nullable();
            $table->decimal('fee_total', 14, 2)->default(0);
            $table->decimal('total_fee_discount', 14, 2)->default(0);
            $table->text('discount_remarks')->nullable();
            $table->date('last_updated')->nullable();
            $table->string('applied_intake_date')->nullable();
            $table->string('deal_id_alt')->nullable();
            $table->string('branch_super_sub')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_data_exports');
    }
};
