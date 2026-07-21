<?php

namespace App\Http\Controllers;

use App\Imports\WorkbookImporter;
use App\Models\Import;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    /**
     * Upload form, with the history underneath it.
     */
    public function index(Request $request)
    {
        $imports = Import::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('import.index', [
            'imports' => $imports,
            'sheets' => WorkbookImporter::SOURCE_SHEETS,
            'stats' => [
                'total'     => Import::count(),
                'completed' => Import::completed()->count(),
                'failed'    => Import::failed()->count(),
                'last'      => Import::completed()->latest()->first(),
            ],
        ]);
    }

    /**
     * Take an upload and run it.
     */
    public function store(Request $request, WorkbookImporter $importer)
    {
        $request->validate([
            'workbook' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'replace'  => ['nullable', 'boolean'],
        ], [
            'workbook.mimes' => 'That is not an Excel workbook. Upload the .xlsx file.',
            'workbook.max'   => 'The workbook is over 20 MB.',
        ]);

        $file = $request->file('workbook');

        $import = $importer->import(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            $request->boolean('replace'),
        );

        if ($import->isFailed()) {
            return back()->with('error', "Import failed: {$import->error}");
        }

        $message = "Imported {$import->filename}: {$import->crm_rows} CRM rows, "
            ."{$import->invoice_rows} invoice rows, {$import->students_built} students built.";

        if ($import->students_skipped > 0) {
            $message .= " {$import->students_skipped} archived student"
                .($import->students_skipped === 1 ? '' : 's')
                .' left untouched.';
        }

        return redirect()->route('import.index')->with('status', $message);
    }

    /**
     * Delete one history entry.
     *
     * This removes the log line only. The rows that import loaded stay where
     * they are -- they may since have been edited, and other imports may have
     * added to them, so there is no safe way to unpick one run's contribution.
     * To clear the data itself, use Delete all on the CRM Data, Invoice and
     * Raw Data tabs, or import again with "Replace existing data" ticked.
     */
    public function destroy(Import $import)
    {
        $name = $import->filename;
        $import->delete();

        return back()->with('status', "Removed {$name} from the history. The imported rows were left in place.");
    }

    /**
     * Clear the whole history.
     */
    public function clear()
    {
        $n = Import::count();

        if ($n === 0) {
            return back()->with('error', 'The history is already empty.');
        }

        Import::query()->delete();

        return back()->with('status', "Cleared {$n} history entr".($n === 1 ? 'y' : 'ies').'. The imported rows were left in place.');
    }

    /**
     * Drop just the failed entries, which is the common tidy-up.
     */
    public function clearFailed()
    {
        $n = Import::failed()->count();

        if ($n === 0) {
            return back()->with('error', 'There are no failed imports to clear.');
        }

        Import::failed()->delete();

        return back()->with('status', "Cleared {$n} failed import".($n === 1 ? '' : 's').'.');
    }
}
