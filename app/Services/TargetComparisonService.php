<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Target;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TargetComparisonService
{
    /**
     * Compare target rows against actual active student enrolments.
     *
     * @param  Collection<int, Target>  $targets
     * @param  array{
     *     search?: string,
     *     partner?: string,
     *     branch?: string,
     *     intake?: string,
     *     course?: string
     * }  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function compare(Collection $targets, array $filters = []): Collection
    {
        return $targets
            ->map(function (Target $target) use ($filters): array {
                $studentQuery = Student::query()->active();

                /*
                |--------------------------------------------------------------------------
                | Match the target dimensions
                |--------------------------------------------------------------------------
                */

                $studentQuery
                    ->where('partner_name', $target->partner_name)
                    ->where('applied_intake_date', $target->intake_code);

                if (filled($target->branch)) {
                    $studentQuery->where('branch', $target->branch);
                }

                /*
                |--------------------------------------------------------------------------
                | Apply Student Details-style filters to actual enrolments
                |--------------------------------------------------------------------------
                */

                $this->applyStudentFilters($studentQuery, $filters);

                $actualEnrolment = (clone $studentQuery)->count();

                /*
                 * paid_fee is used as the received/actual commission figure.
                 * Change this to credit_fee if your target should compare
                 * against commission entitlement instead of amount received.
                 */
                $actualCommission = (float) (clone $studentQuery)->sum('paid_fee');

                $targetEnrolment = (int) $target->target_enrolment;
                $targetCommission = (float) ($target->target_commission ?? 0);

                $enrolmentVariance = $actualEnrolment - $targetEnrolment;
                $commissionVariance = $actualCommission - $targetCommission;

                $enrolmentPercentage = $targetEnrolment > 0
                    ? round(($actualEnrolment / $targetEnrolment) * 100, 1)
                    : null;

                return [
                    'target' => $target,
                    'partner_name' => $target->partner_name,
                    'intake_code' => $target->intake_code,
                    'branch' => $target->branch,
                    'target_enrolment' => $targetEnrolment,
                    'actual_enrolment' => $actualEnrolment,
                    'enrolment_variance' => $enrolmentVariance,
                    'enrolment_pct' => $enrolmentPercentage,
                    'target_commission' => $targetCommission,
                    'actual_commission' => $actualCommission,
                    'commission_variance' => $commissionVariance,
                ];
            })
            ->values();
    }

    /**
     * Apply the same search/filter names used by Student Details.
     */
    private function applyStudentFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $partner = trim((string) ($filters['partner'] ?? ''));
        $branch = trim((string) ($filters['branch'] ?? ''));
        $intake = trim((string) ($filters['intake'] ?? ''));
        $course = trim((string) ($filters['course'] ?? ''));

        if ($search !== '') {
            $term = '%' . $search . '%';

            $query->where(function (Builder $subQuery) use ($term) {
                $subQuery
                    ->where('name', 'like', $term)
                    ->orWhere('client_id_crm', 'like', $term)
                    ->orWhere('client_id_invoice', 'like', $term)
                    ->orWhere('contact_id', 'like', $term)
                    ->orWhere('product_name', 'like', $term);
            });
        }

        if ($partner !== '') {
            $query->where('partner_name', $partner);
        }

        if ($branch !== '') {
            $query->where('branch', $branch);
        }

        if ($intake !== '') {
            $query->where('applied_intake_date', $intake);
        }

        if ($course !== '') {
            $query->where('product_name', 'like', '%' . $course . '%');
        }
    }
}