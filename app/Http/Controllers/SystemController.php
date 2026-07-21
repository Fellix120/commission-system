<?php
namespace App\Http\Controllers;

use App\Models\CrmData;
use App\Models\Import;
use App\Models\Invoice;
use App\Models\RawDataExport;
use App\Models\Student;
use App\Models\StudentIntakeCommission;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class SystemController extends Controller
{
    public function fresh()
    {
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        return redirect()->route('dashboard')->with('status', 'All tables were cleared and rebuilt with migrate:fresh --seed.');
    }

    /**
     * Wipe every table that imports populate, plus the import history.
     *
     * Targets are left alone -- they are entered by hand, not imported.
     * FK checks are disabled around the truncates because MySQL refuses to
     * truncate students while student_intake_commissions references it.
     */
    public function clearImportedData()
    {
        Schema::disableForeignKeyConstraints();

        try {
            StudentIntakeCommission::truncate();
            Student::truncate();
            Invoice::truncate();
            CrmData::truncate();
            RawDataExport::truncate();
            Import::truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return redirect()->route('import.index')
            ->with('status', 'All imported data was cleared: raw data, CRM data, invoices, students and import history. Targets were kept.');
    }
}