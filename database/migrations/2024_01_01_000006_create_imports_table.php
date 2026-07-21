<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Import history.
 *
 * One row per uploaded workbook: what was uploaded, when, what came out of it,
 * and whether it replaced the previous data. The row counts are stored rather
 * than recalculated, so the log still reads correctly after the underlying rows
 * have been edited or deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();

            $table->string('filename');                        // as uploaded
            $table->unsignedBigInteger('file_size')->default(0);   // bytes
            $table->string('status', 20)->default('pending');      // pending|completed|failed
            $table->boolean('replaced_existing')->default(false);  // did it wipe first?

            // What the file produced. Counted at import time.
            $table->unsignedInteger('raw_rows')->default(0);
            $table->unsignedInteger('crm_rows')->default(0);
            $table->unsignedInteger('invoice_rows')->default(0);
            $table->unsignedInteger('students_built')->default(0);
            $table->unsignedInteger('students_skipped')->default(0);  // archived, left frozen

            // Which sheets were actually found in the upload.
            $table->text('sheets_found')->nullable();

            // Student Details is not imported, but it IS read for a sanity
            // check: if the value paste claims a bigger fee than CRM Data can
            // support, the export is missing course rows and the rebuilt
            // figures are understated. Recording that here means a short
            // export is visible in the log instead of silently wrong.
            $table->unsignedInteger('students_understated')->default(0);
            $table->text('understated_note')->nullable();

            $table->text('error')->nullable();                 // if status = failed
            $table->unsignedInteger('duration_ms')->default(0);

            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
