<?php

namespace App\Services;

/**
 * The 'Student Details-Formula' sheet, as data.
 *
 * The workbook keeps a companion tab showing the live formula behind every
 * Student Details column. That tab is the documentation for how the numbers
 * are derived, so it is reproduced here rather than dropped: each entry pairs
 * the original Excel formula with the PHP that replaced it, and points at the
 * method in StudentDetailService that actually runs.
 *
 * Formulas are quoted verbatim from the workbook (with the _xlfn. / _xlws.
 * compatibility prefixes that Excel writes into the file stripped, since those
 * are storage artefacts rather than anything the user typed).
 */
class FormulaMapService
{
    /**
     * The lookup key that drives the whole sheet.
     */
    public const KEY_FORMULA = "=UNIQUE('CRM Data'!G:G)";

    /**
     * @return array<int, array<string, string>>
     */
    public function columns(): array
    {
        return [
            [
                'column' => 'A',
                'field' => 'Branch',
                'db' => 'branch',
                'formula' => "=XLOOKUP(\$G2,'CRM Data'!\$G:\$G,'CRM Data'!A:A,,0)",
                'kind' => 'lookup',
                'php' => '$first->branch',
                'method' => 'refreshOne()',
                'note' => 'First matching CRM row for this Client ID.',
            ],
            [
                'column' => 'B',
                'field' => 'Applied Intake Date',
                'db' => 'applied_intake_date',
                'formula' => "=XLOOKUP(\$G2,'CRM Data'!\$G:\$G,'CRM Data'!B:B,,0)",
                'kind' => 'lookup',
                'php' => '$first->applied_intake_date',
                'method' => 'refreshOne()',
                'note' => 'Also the match key for targets.',
            ],
            [
                'column' => 'C',
                'field' => 'Contact ID',
                'db' => 'contact_id',
                'formula' => "=XLOOKUP(\$G2,'CRM Data'!\$G:\$G,'CRM Data'!C:C,,0)",
                'kind' => 'lookup',
                'php' => '$first->contact_id',
                'method' => 'refreshOne()',
                'note' => '',
            ],
            [
                'column' => 'D',
                'field' => 'Sub Agent Name',
                'db' => 'sub_agent_name',
                'formula' => "=XLOOKUP(\$G2,'CRM Data'!\$G:\$G,'CRM Data'!D:D,,0)",
                'kind' => 'lookup',
                'php' => '$first->sub_agent_name',
                'method' => 'refreshOne()',
                'note' => '',
            ],
            [
                'column' => 'E',
                'field' => 'Super Agent Name',
                'db' => 'super_agent_name',
                'formula' => "=XLOOKUP(\$G2,'CRM Data'!\$G:\$G,'CRM Data'!E:E,,0)",
                'kind' => 'lookup',
                'php' => '$first->super_agent_name',
                'method' => 'refreshOne()',
                'note' => '',
            ],
            [
                'column' => 'F',
                'field' => 'Partner Name',
                'db' => 'partner_name',
                'formula' => "=XLOOKUP(\$G2,'CRM Data'!\$G:\$G,'CRM Data'!F:F,,0)",
                'kind' => 'lookup',
                'php' => '$first->partner_name',
                'method' => 'refreshOne()',
                'note' => 'Match key for targets.',
            ],
            [
                'column' => 'G',
                'field' => 'Client ID',
                'db' => 'client_id_crm',
                'formula' => "=UNIQUE('CRM Data'!G:G)",
                'kind' => 'key',
                'php' => "CrmData::completed()->distinct()->pluck('client_id')",
                'method' => 'refreshAll()',
                'note' => 'The key. Spills the student list; every other column looks up against it.',
            ],
            [
                'column' => 'H',
                'field' => 'Name',
                'db' => 'name',
                'formula' => "=XLOOKUP(\$G2,'CRM Data'!\$G:\$G,'CRM Data'!H:H,,0)",
                'kind' => 'lookup',
                'php' => '$first->name',
                'method' => 'refreshOne()',
                'note' => '',
            ],
            [
                'column' => 'I',
                'field' => 'Product Name',
                'db' => 'product_name',
                'formula' => "=TEXTJOIN(\" + \",TRUE,FILTER('CRM Data'!I:I,'CRM Data'!G:G=\$G2))",
                'kind' => 'aggregate',
                'php' => "implode(' + ', array_unique(\$names))",
                'method' => 'joinedProductNames()',
                'note' => 'Joins every course for the student. A packaged degree shows as "Diploma + Bachelor".',
            ],
            [
                'column' => 'J',
                'field' => 'Start Date',
                'db' => 'start_date',
                'formula' => "=MINIFS('CRM Data'!J:J,'CRM Data'!G:G,\$G2)",
                'kind' => 'aggregate',
                'php' => '$rows->min("start_date")',
                'method' => 'refreshOne()',
                'note' => 'Earliest course start.',
            ],
            [
                'column' => 'K',
                'field' => 'End Date',
                'db' => 'end_date',
                'formula' => "=MAXIFS('CRM Data'!K:K,'CRM Data'!G:G,\$G2)",
                'kind' => 'aggregate',
                'php' => '$rows->max("end_date")',
                'method' => 'refreshOne()',
                'note' => 'Latest course end.',
            ],
            [
                'column' => 'L',
                'field' => 'Fee Total',
                'db' => 'fee_total',
                'formula' => "=SUMIFS('CRM Data'!L:L,'CRM Data'!G:G,\$G2)",
                'kind' => 'aggregate',
                'php' => '$rows->sum("fee_total")',
                'method' => 'refreshOne()',
                'note' => 'Sums every course. Missing CRM rows understate this.',
            ],
        ];
    }

    /**
     * Columns that exist on Student Details but have no formula on the
     * companion tab -- they are typed by an operator or come from Invoice.
     *
     * @return array<int, array<string, string>>
     */
    public function derivedColumns(): array
    {
        return [
            [
                'column' => 'M',
                'field' => 'Course Duration',
                'db' => 'course_duration',
                'formula' => '=(K2-J2)/365',
                'kind' => 'derived',
                'php' => 'round($days / 365, 6)',
                'method' => 'courseDuration()',
                'note' => 'End minus start, in years.',
            ],
            [
                'column' => 'O',
                'field' => 'Credit Fee (CR fee)',
                'db' => 'credit_fee',
                'formula' => 'typed by hand',
                'kind' => 'manual',
                'php' => '$fee / $durationYears',
                'method' => 'creditFee()',
                'note' => 'The commissionable fee. Seeded as an estimate, then yours -- a refresh never overwrites it.',
            ],
            [
                'column' => 'Q',
                'field' => 'Paid Fee',
                'db' => 'paid_fee',
                'formula' => 'from Invoice',
                'kind' => 'invoice',
                'php' => '$invoices->sum("total_fee")',
                'method' => 'refreshOne()',
                'note' => 'Matched on Invoice SID = Client ID-Invoice.',
            ],
            [
                'column' => 'S',
                'field' => 'Remaining Fee',
                'db' => 'remaining_fee',
                'formula' => '=O2-Q2+U2',
                'kind' => 'derived',
                'php' => 'max($creditFee - $paidFee + $adj, 0)',
                'method' => 'remainingFee()',
                'note' => 'Credit - paid + adjustment. Drives the archive guard.',
            ],
            [
                'column' => 'T',
                'field' => 'Remaining Bonus',
                'db' => 'remaining_bonus',
                'formula' => '=P2-R2+V2',
                'kind' => 'derived',
                'php' => 'max($bonusDue - $paidBonus + $adj, 0)',
                'method' => 'remainingBonus()',
                'note' => 'Bonus due - paid + adjustment.',
            ],
            [
                'column' => 'Y+',
                'field' => 'T2-2025, T3-2025, T1-2026 …',
                'db' => 'student_intake_commissions',
                'formula' => 'one column per intake',
                'kind' => 'invoice',
                'php' => "groupBy('commission_intake')->sum('total_fee')",
                'method' => 'syncIntakeCommissions()',
                'note' => 'Rows here, not columns -- a new intake needs no schema change.',
            ],
        ];
    }
}
