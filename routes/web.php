<?php

use App\Http\Controllers\CrmDataController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FormulaController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Requirement 1 + 2: Student Details display with intake commission columns
Route::get('/students', [StudentController::class, 'index'])->name('students.index');

// Literal segments must be declared before /students/{student}, or the router
// binds "refresh" and "bulk-delete" as a student id and 404s.
Route::post('/students/refresh', [StudentController::class, 'refresh'])->name('students.refresh');
Route::post('/students/bulk-archive', [StudentController::class, 'bulkArchive'])->name('students.bulk-archive');
Route::post('/students/bulk-delete', [StudentController::class, 'bulkDestroy'])->name('students.bulk-delete');

Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

// Requirement 3: archive
Route::post('/students/{student}/archive', [StudentController::class, 'archive'])->name('students.archive');
Route::post('/students/{student}/unarchive', [StudentController::class, 'unarchive'])->name('students.unarchive');

// Requirement 4: targets vs enrolment
Route::get('/targets', [TargetController::class, 'index'])->name('targets.index');
Route::post('/targets', [TargetController::class, 'store'])->name('targets.store');
Route::delete('/targets/{target}', [TargetController::class, 'destroy'])->name('targets.destroy');

// Source tabs
Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
Route::delete('/invoices', [InvoiceController::class, 'truncate'])->name('invoices.truncate');
Route::get('/student-details-formula', [FormulaController::class, 'index'])->name('formula.index');

Route::get('/crm-data', [CrmDataController::class, 'index'])->name('crm.index');
Route::delete('/crm-data/{crmDatum}', [CrmDataController::class, 'destroy'])->name('crm.destroy');
Route::delete('/crm-data', [CrmDataController::class, 'truncate'])->name('crm.truncate');

Route::get('/raw-data', [CrmDataController::class, 'raw'])->name('crm.raw');
Route::delete('/raw-data/{rawDatum}', [CrmDataController::class, 'destroyRaw'])->name('crm.raw.destroy');
Route::delete('/raw-data', [CrmDataController::class, 'truncateRaw'])->name('crm.raw.truncate');

// Import + history
Route::get('/import', [ImportController::class, 'index'])->name('import.index');
Route::post('/import', [ImportController::class, 'store'])->name('import.store');

// Literal routes before the wildcard, or "clear" binds as an id.
Route::delete('/import/history/clear', [ImportController::class, 'clear'])->name('import.clear');
Route::delete('/import/history/failed', [ImportController::class, 'clearFailed'])->name('import.clear-failed');
Route::delete('/import/history/{import}', [ImportController::class, 'destroy'])->name('import.destroy');

// Export
Route::get('/export/students', [ExportController::class, 'students'])->name('export.students');
Route::get('/export/formula', [ExportController::class, 'formula'])->name('export.formula');
Route::get('/export/invoices', [ExportController::class, 'invoices'])->name('export.invoices');
Route::get('/export/crm-data', [ExportController::class, 'crm'])->name('export.crm');
Route::get('/export/raw-data', [ExportController::class, 'raw'])->name('export.raw');

Route::post('/system/migrate-fresh', [SystemController::class, 'fresh'])->name('system.fresh');
