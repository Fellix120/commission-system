<?php

namespace Tests\Unit;

use App\Services\StudentDetailService;
use Tests\TestCase;

/**
 * Expected values are taken from the sample rows in
 * Templete_for_Commission_Working.xlsx ("Student Details" tab).
 */
class StudentDetailServiceTest extends TestCase
{
    protected StudentDetailService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StudentDetailService();
    }

    /** Excel column M: (End - Start) / 365 */
    public function test_course_duration_matches_workbook(): void
    {
        // Tanzim Amin TANU: 14/04/2025 -> 22/06/2025, sheet shows 0.18904109589041096
        $this->assertEqualsWithDelta(
            0.189041,
            $this->service->courseDuration('2025-04-14', '2025-06-22'),
            0.0001
        );

        // MUSFIQUR RAHMAN LABIB: 14/04/2025 -> 15/07/2028, sheet shows 3.254794520547945
        $this->assertEqualsWithDelta(
            3.254795,
            $this->service->courseDuration('2025-04-14', '2028-07-15'),
            0.0001
        );

        // Meri Akter: 30/06/2025 -> 31/12/2026, sheet shows 1.5041095890410958
        $this->assertEqualsWithDelta(
            1.504110,
            $this->service->courseDuration('2025-06-30', '2026-12-31'),
            0.0001
        );
    }

    public function test_course_duration_handles_missing_dates(): void
    {
        $this->assertSame(0.0, $this->service->courseDuration(null, '2026-01-01'));
        $this->assertSame(0.0, $this->service->courseDuration('2026-01-01', null));
    }

    /** Excel column O: CR fee. Meri Akter 52200 / 1.5041 = 34704.92 on the sheet. */
    public function test_credit_fee_prorates_courses_longer_than_a_year(): void
    {
        $this->assertEqualsWithDelta(
            34704.92,
            $this->service->creditFee(52200, 1.5041095890410958),
            0.02
        );
    }

    public function test_credit_fee_is_full_fee_for_short_courses(): void
    {
        // TANU: 20500 over 0.19 years -> full 20500 on the sheet.
        $this->assertSame(20500.0, $this->service->creditFee(20500, 0.189041));
    }

    /** Excel column S: Credit - Paid + Adjustment, floored at zero. */
    public function test_remaining_fee_matches_workbook(): void
    {
        // TANU: 20500 credit, 21100 paid, -600 adjustment -> 0
        $this->assertSame(0.0, $this->service->remainingFee(20500, 21100, -600));

        // LABIB: 34590 credit, nothing paid -> 34590
        $this->assertSame(34590.0, $this->service->remainingFee(34590, 0));

        // jannatul ferdous: 52800 credit, 17094 paid -> 35706
        $this->assertSame(35706.0, $this->service->remainingFee(52800, 17094));
    }

    /** Excel column T: Bonus Due - Paid Bonus. */
    public function test_remaining_bonus_matches_workbook(): void
    {
        // TANU: 1000 due, 500 paid -> 500
        $this->assertSame(500.0, $this->service->remainingBonus(1000, 500));

        // LABIB: 1000 due, none paid -> 1000
        $this->assertSame(1000.0, $this->service->remainingBonus(1000, 0));
    }

    public function test_remaining_never_goes_negative(): void
    {
        $this->assertSame(0.0, $this->service->remainingFee(1000, 5000));
        $this->assertSame(0.0, $this->service->remainingBonus(500, 900));
    }

    /** Excel column J: TEXTJOIN(" + ", TRUE, FILTER(...)) over the courses. */
    public function test_product_names_are_joined(): void
    {
        $rows = collect([
            (object) ['product_name' => 'Academic English Program (Elementary to Advanced)'],
            (object) ['product_name' => 'Bachelor of Commerce'],
        ]);

        $this->assertSame(
            'Academic English Program (Elementary to Advanced) + Bachelor of Commerce',
            $this->service->joinedProductNames($rows)
        );
    }

    /**
     * TEXTJOIN does not deduplicate, so neither may the port.
     *
     * The workbook proves it: client 12765881 (Mahjabin Suchi) is value-pasted
     * as "Master of Public Health + Master of Public Health" -- one entry per
     * CRM row. Collapsing repeats would hide the second enrolment and make a
     * two-row student indistinguishable from a one-row student.
     */
    public function test_repeated_course_names_are_kept(): void
    {
        $rows = collect([
            (object) ['product_name' => 'Master of Public Health'],
            (object) ['product_name' => 'Master of Public Health'],
        ]);

        $this->assertSame(
            'Master of Public Health + Master of Public Health',
            $this->service->joinedProductNames($rows)
        );
    }

    /** The TRUE argument to TEXTJOIN skips blanks. */
    public function test_blank_product_names_are_skipped(): void
    {
        $rows = collect([
            (object) ['product_name' => 'Diploma of Business'],
            (object) ['product_name' => null],
            (object) ['product_name' => 'Bachelor of Business'],
        ]);

        $this->assertSame(
            'Diploma of Business + Bachelor of Business',
            $this->service->joinedProductNames($rows)
        );
    }
}
