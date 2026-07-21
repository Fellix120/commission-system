@extends('layouts.app')
@section('title', 'Dashboard - Commission Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>Dashboard</h1>

            <span class="card__sub">
                Last value-paste refresh:
                {{ $stats['last_refresh'] ? \Carbon\Carbon::parse($stats['last_refresh'])->format('d/m/Y H:i') : 'never' }}
            </span>
        </div>

        <div class="page-head__actions">
            <a href="{{ route('students.index', request()->query()) }}" class="btn btn--outlined">
                <span class="material-symbols-outlined">table_view</span>
                Student Details
            </a>

            <form method="POST" action="{{ route('students.refresh') }}">
                @csrf

                <button type="submit" class="btn btn--filled">
                    <span class="material-symbols-outlined">refresh</span>
                    Refresh values
                </button>
            </form>
        </div>
    </div>

    {{-- Use the same reusable filter component as Student Details. --}}
    @include('partials.data-filters', [
        'partners' => $partners,
        'branches' => $branches,
        'intakes' => $intakes,
        'courses' => $courses,
        'clearUrl' => route('dashboard'),
    ])

    {{-- Dashboard statistics --}}
    <div class="stats dashboard-stats">
        <div class="stat stat--blue">
            <div class="stat__label">
                <span class="material-symbols-outlined">groups</span>
                Active students
            </div>

            <div class="stat__value">
                {{ number_format($stats['active_students']) }}
            </div>

            <div class="stat__hint">
                {{ number_format($stats['archived_students']) }} archived
            </div>
        </div>

        <div class="stat stat--yellow">
            <div class="stat__label">
                <span class="material-symbols-outlined">request_quote</span>
                Credit fee
            </div>

            <div class="stat__value">
                ${{ number_format($stats['total_credit_fee'], 0) }}
            </div>

            <div class="stat__hint">
                Total commission claimable
            </div>
        </div>

        <div class="stat stat--green">
            <div class="stat__label">
                <span class="material-symbols-outlined">payments</span>
                Paid fee
            </div>

            <div class="stat__value">
                ${{ number_format($stats['total_paid_fee'], 0) }}
            </div>

            <div class="stat__hint">
                Commission already received
            </div>
        </div>

        <div class="stat stat--red">
            <div class="stat__label">
                <span class="material-symbols-outlined">pending</span>
                Remaining fee
            </div>

            <div class="stat__value">
                ${{ number_format($stats['total_remaining'], 0) }}
            </div>

            <div class="stat__hint">
                Still outstanding
            </div>
        </div>

        <div class="stat">
            <div class="stat__label">
                <span class="material-symbols-outlined">savings</span>
                Net commission
            </div>

            <div class="stat__value">
                ${{ number_format($stats['net_commission'], 0) }}
            </div>

            <div class="stat__hint">
                Branch share ${{ number_format($stats['branch_commission'], 0) }}
            </div>
        </div>

        <div class="stat">
            <div class="stat__label">
                <span class="material-symbols-outlined">table_chart</span>
                Source rows
            </div>

            <div class="stat__value">
                {{ number_format($stats['crm_completed']) }}
            </div>

            <div class="stat__hint">
                CRM completed · {{ number_format($stats['invoice_rows']) }} invoices
            </div>
        </div>
    </div>

    <div class="detail-grid dashboard-detail-grid">
        {{-- Commission received by intake --}}
        <div class="card">
            <div class="card__head">
                <span class="material-symbols-outlined" style="color: var(--g-yellow)">
                    event
                </span>

                <div>
                    <div class="card__title">Commission received by intake</div>
                    <div class="card__sub">Results follow the selected dashboard filters</div>
                </div>
            </div>

            <div class="table-wrap dashboard-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Intake</th>
                            <th class="is-num">Received</th>
                            <th class="dashboard-share-column">Share</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            $visibleIntakeTotals = $intakeTotals->filter(fn($amount) => (float) $amount > 0);
                            $maxIntakeAmount = (float) ($visibleIntakeTotals->max() ?: 1);
                        @endphp

                        @forelse ($visibleIntakeTotals as $code => $amount)
                            <tr>
                                <td>
                                    <span class="badge badge--blue">{{ $code }}</span>
                                </td>

                                <td class="is-money">
                                    ${{ number_format((float) $amount, 0) }}
                                </td>

                                <td>
                                    <div class="progress">
                                        <div class="progress__fill"
                                            style="width: {{ min(((float) $amount / $maxIntakeAmount) * 100, 100) }}%">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="dashboard-empty-cell">
                                    No commission intake data found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top partners --}}
        <div class="card">
            <div class="card__head">
                <span class="material-symbols-outlined" style="color: var(--g-blue)">
                    school
                </span>

                <div>
                    <div class="card__title">Top partners</div>
                    <div class="card__sub">Ordered by paid commission</div>
                </div>
            </div>

            <div class="table-wrap dashboard-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Partner</th>
                            <th class="is-num">Students</th>
                            <th class="is-num">Credit</th>
                            <th class="is-num">Paid</th>
                            <th class="is-num">Remaining</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($byPartner as $partner)
                            <tr>
                                <td>
                                    <span class="cell-truncate" title="{{ $partner->partner_name }}">
                                        {{ $partner->partner_name }}
                                    </span>
                                </td>

                                <td class="is-num">
                                    {{ number_format((int) $partner->students) }}
                                </td>

                                <td class="is-money">
                                    ${{ number_format((float) $partner->credit_fee, 0) }}
                                </td>

                                <td class="is-money">
                                    ${{ number_format((float) $partner->paid, 0) }}
                                </td>

                                <td class="is-money">
                                    @if ((float) $partner->remaining > 0)
                                        <span style="color: var(--g-red)">
                                            ${{ number_format((float) $partner->remaining, 0) }}
                                        </span>
                                    @else
                                        <span style="color: var(--g-green)">$0</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="dashboard-empty-cell">
                                    No partner data found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .data-filter-form {
            margin-bottom: 20px;
        }

        .dashboard-stats {
            grid-template-columns: repeat(6, minmax(170px, 1fr));
        }

        .dashboard-detail-grid {
            align-items: start;
        }

        .dashboard-table-wrap {
            max-height: none;
        }

        .dashboard-share-column {
            width: 40%;
        }

        .dashboard-empty-cell {
            padding: 28px 16px !important;
            text-align: center;
            color: var(--g-grey-500, #667085);
        }

        @media (max-width: 1500px) {
            .dashboard-stats {
                grid-template-columns: repeat(3, minmax(200px, 1fr));
            }
        }

        @media (max-width: 900px) {
            .dashboard-stats {
                grid-template-columns: repeat(2, minmax(180px, 1fr));
            }

            .dashboard-detail-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }

            .page-head__actions {
                width: 100%;
            }

            .page-head__actions .btn,
            .page-head__actions form {
                width: 100%;
            }

            .page-head__actions form .btn {
                justify-content: center;
            }
        }
    </style>
@endsection
