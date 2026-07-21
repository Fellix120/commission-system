<?php

namespace Tests\Unit;

use App\Services\FormulaMapService;
use Tests\TestCase;

/**
 * The formula map is documentation, so the thing worth testing is that it
 * stays faithful to the workbook and in step with the code it describes.
 */
class FormulaMapServiceTest extends TestCase
{
    protected FormulaMapService $map;

    protected function setUp(): void
    {
        parent::setUp();
        $this->map = new FormulaMapService();
    }

    public function test_it_documents_every_column_on_the_formula_sheet(): void
    {
        $columns = array_column($this->map->columns(), 'column');

        // The sheet is A..L -- twelve columns, no gaps.
        $this->assertSame(range('A', 'L'), $columns);
    }

    public function test_the_key_is_the_unique_spill_over_crm_client_ids(): void
    {
        $this->assertSame("=UNIQUE('CRM Data'!G:G)", FormulaMapService::KEY_FORMULA);
    }

    public function test_lookup_columns_quote_the_workbook_verbatim(): void
    {
        $branch = collect($this->map->columns())->firstWhere('column', 'A');

        $this->assertSame(
            "=XLOOKUP(\$G2,'CRM Data'!\$G:\$G,'CRM Data'!A:A,,0)",
            $branch['formula']
        );
    }

    public function test_product_name_uses_textjoin_with_the_workbooks_separator(): void
    {
        $product = collect($this->map->columns())->firstWhere('column', 'I');

        // The separator matters: joinedProductNames() must emit the same " + ".
        $this->assertStringContainsString('TEXTJOIN(" + "', $product['formula']);
        $this->assertStringContainsString('FILTER', $product['formula']);
    }

    public function test_aggregate_columns_are_minifs_maxifs_and_sumifs(): void
    {
        $byColumn = collect($this->map->columns())->keyBy('column');

        $this->assertStringContainsString('MINIFS', $byColumn['J']['formula']);
        $this->assertStringContainsString('MAXIFS', $byColumn['K']['formula']);
        $this->assertStringContainsString('SUMIFS', $byColumn['L']['formula']);
    }

    public function test_every_entry_names_the_method_that_replaced_it(): void
    {
        foreach (array_merge($this->map->columns(), $this->map->derivedColumns()) as $entry) {
            $this->assertNotEmpty($entry['method'], "{$entry['field']} has no method");
            $this->assertNotEmpty($entry['php'], "{$entry['field']} has no PHP");
        }
    }

    public function test_credit_fee_is_flagged_as_operator_owned(): void
    {
        $creditFee = collect($this->map->derivedColumns())->firstWhere('column', 'O');

        // If this ever stops being 'manual', refreshAll() is overwriting
        // an operator's figure -- which the Summary tab forbids.
        $this->assertSame('manual', $creditFee['kind']);
    }
}
