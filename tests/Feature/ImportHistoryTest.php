<?php

namespace Tests\Feature;

use App\Models\Import;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function log(array $attributes = []): Import
    {
        return Import::create(array_merge([
            'filename' => 'workbook.xlsx',
            'status' => 'completed',
            'crm_rows' => 39,
            'invoice_rows' => 54,
            'students_built' => 37,
        ], $attributes));
    }

    public function test_history_lists_imports_newest_first(): void
    {
        $this->log(['filename' => 'older.xlsx', 'created_at' => now()->subDay()]);
        $this->log(['filename' => 'newer.xlsx', 'created_at' => now()]);

        $this->get(route('import.index'))
            ->assertOk()
            ->assertSeeInOrder(['newer.xlsx', 'older.xlsx']);
    }

    public function test_history_can_be_filtered_by_status(): void
    {
        $this->log(['filename' => 'good.xlsx']);
        $this->log(['filename' => 'bad.xlsx', 'status' => 'failed', 'error' => 'Corrupt file']);

        $this->get(route('import.index', ['status' => 'failed']))
            ->assertSee('bad.xlsx')
            ->assertDontSee('good.xlsx');
    }

    public function test_a_single_entry_can_be_deleted(): void
    {
        $import = $this->log();

        $this->delete(route('import.destroy', $import))->assertRedirect();

        $this->assertModelMissing($import);
    }

    public function test_the_whole_history_can_be_cleared(): void
    {
        $this->log();
        $this->log(['filename' => 'second.xlsx']);

        $this->delete(route('import.clear'))->assertRedirect();

        $this->assertSame(0, Import::count());
    }

    public function test_clearing_an_empty_history_reports_rather_than_pretending(): void
    {
        $this->delete(route('import.clear'))->assertSessionHas('error');
    }

    public function test_only_failed_entries_can_be_cleared(): void
    {
        $ok = $this->log(['filename' => 'good.xlsx']);
        $bad = $this->log(['filename' => 'bad.xlsx', 'status' => 'failed']);

        $this->delete(route('import.clear-failed'))->assertRedirect();

        $this->assertModelExists($ok);
        $this->assertModelMissing($bad);
    }

    /**
     * Deleting history must not touch the rows an import loaded. The log is a
     * record of a run, not a handle on its data.
     */
    public function test_deleting_history_leaves_the_imported_rows_alone(): void
    {
        \App\Models\CrmData::create([
            'client_id' => 'K1', 'name' => 'Kept Kim', 'status' => 'Completed', 'fee_total' => 1000,
        ]);
        $import = $this->log();

        $this->delete(route('import.destroy', $import));

        $this->assertSame(1, \App\Models\CrmData::count());
    }

    public function test_upload_rejects_a_non_excel_file(): void
    {
        $this->post(route('import.store'), [
            'workbook' => \Illuminate\Http\UploadedFile::fake()->create('notes.txt', 10),
        ])->assertSessionHasErrors('workbook');
    }

    public function test_the_model_summarises_a_run(): void
    {
        $import = $this->log(['raw_rows' => 8, 'file_size' => 1536, 'duration_ms' => 2400]);

        $this->assertSame(101, $import->totalRows());   // 8 + 39 + 54
        $this->assertSame('1.5 KB', $import->humanSize());
        $this->assertSame('2.4s', $import->humanDuration());
    }

    public function test_an_understated_import_is_flagged(): void
    {
        $clean = $this->log();
        $short = $this->log(['students_understated' => 7, 'understated_note' => 'Missing course rows']);

        $this->assertFalse($clean->hasUnderstated());
        $this->assertTrue($short->hasUnderstated());
    }
}
