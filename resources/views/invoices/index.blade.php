@extends('layouts.app')

@section('title', 'Invoice - Commission Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>Invoice</h1>
            <span class="card__sub">Report received from Head Office and college</span>
        </div>

        <div class="page-actions">
            <a href="{{ route('export.invoices', request()->query()) }}" class="btn btn--outlined">
                <span class="material-symbols-outlined">download</span>
                Export
            </a>

            {{-- Optional destructive actions
            <form method="POST" action="{{ route('system.fresh') }}" id="freshSystemForm">
                @csrf

                <button type="submit" class="btn btn--outlined btn--danger">
                    <span class="material-symbols-outlined">delete_forever</span>
                    Clear all tables
                </button>
            </form>

            <form method="POST" action="{{ route('invoices.truncate') }}" id="deleteAllInvoicesForm">
                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="btn btn--outlined btn--danger"
                    {{ $invoices->total() ? '' : 'disabled' }}
                >
                    <span class="material-symbols-outlined">delete_sweep</span>
                    Delete all
                </button>
            </form>
            --}}
        </div>
    </div>

    <div class="stats">
        <div class="stat">
            <div class="stat__label">Total fee</div>
            <div class="stat__value">
                ${{ number_format($totals['total_fee'], 0) }}
            </div>
        </div>

        <div class="stat stat--green">
            <div class="stat__label">Net commission</div>
            <div class="stat__value">
                ${{ number_format($totals['net_commission'], 0) }}
            </div>
        </div>

        <div class="stat stat--blue">
            <div class="stat__label">Branch commission</div>
            <div class="stat__value">
                ${{ number_format($totals['branch_commission'], 0) }}
            </div>
        </div>

        <div class="stat stat--yellow">
            <div class="stat__label">Bonus</div>
            <div class="stat__value">
                ${{ number_format($totals['bonus'], 0) }}
            </div>
        </div>
    </div>

    @include('partials.data-filters', [
        'partners' => $partners,
        'branches' => $branches,
        'intakes' => $intakes,
        'courses' => $courses,
        'clearUrl' => route('invoices.index'),
    ])

    @if ($invoices->isEmpty())
        <div class="card empty">
            <span class="material-symbols-outlined">receipt_long</span>
            <h3>No invoice rows found</h3>

            @if (request()->hasAny(['search', 'partner', 'branch', 'intake', 'course', 'status']))
                <p>No invoice rows match the selected filters.</p>

                <p style="margin-top:16px">
                    <a href="{{ route('invoices.index') }}" class="btn btn--tonal">
                        <span class="material-symbols-outlined">filter_alt_off</span>
                        Clear filters
                    </a>
                </p>
            @else
                <p>Import invoice data to display invoice rows here.</p>
            @endif
        </div>
    @else
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="col-sticky">Application ID</th>
                            <th>Branch</th>
                            <th>Subagent</th>
                            <th>Provider (Super Agent)</th>
                            <th>SID</th>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Start</th>
                            <th>Commission intake</th>
                            <th class="is-num">Total fee</th>
                            <th class="is-num">Rate</th>
                            <th class="is-num">Commission</th>
                            <th class="is-num">Discount</th>
                            <th class="is-num">Net commission</th>
                            <th class="is-num">Royalty</th>
                            <th class="is-num">Bonus</th>
                            <th class="is-num">EEVS HO</th>
                            <th class="is-num">Branch comm.</th>
                            <th class="is-num">Branch bonus</th>
                            <th>Remarks</th>
                            <th>PO</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($invoices as $inv)
                            <tr>
                                <td class="col-sticky">
                                    {{ $inv->application_id }}
                                </td>

                                <td>{{ $inv->branch }}</td>

                                <td>{{ $inv->subagent ?? '–' }}</td>

                                <td>
                                    <span class="cell-truncate" title="{{ $inv->provider_name }}">
                                        {{ $inv->provider_name }}
                                    </span>
                                </td>

                                <td>{{ $inv->sid }}</td>

                                <td>{{ $inv->name }}</td>

                                <td>
                                    <span class="cell-truncate" title="{{ $inv->course }}">
                                        {{ $inv->course }}
                                    </span>
                                </td>

                                <td>
                                    {{ $inv->start_date?->format('d/m/Y') }}
                                </td>

                                <td>
                                    <span class="badge badge--blue">
                                        {{ $inv->commission_intake }}
                                    </span>
                                </td>

                                <td class="is-money">
                                    {{ number_format((float) $inv->total_fee, 2) }}
                                </td>

                                <td class="is-num">
                                    {{ number_format((float) $inv->rate * 100, 2) }}%
                                </td>

                                <td class="is-money">
                                    {{ number_format((float) $inv->commission, 2) }}
                                </td>

                                <td class="is-money">
                                    {{ number_format((float) $inv->discount, 2) }}
                                </td>

                                <td class="is-money">
                                    {{ number_format((float) $inv->net_commission, 2) }}
                                </td>

                                <td class="is-num">
                                    {{ number_format((float) $inv->royalty_rate * 100, 1) }}%
                                </td>

                                <td class="is-money">
                                    {{ number_format((float) $inv->bonus, 2) }}
                                </td>

                                <td class="is-money">
                                    {{ number_format((float) $inv->eevs_ho, 2) }}
                                </td>

                                <td class="is-money">
                                    {{ number_format((float) $inv->branch_commission, 2) }}
                                </td>

                                <td class="is-money">
                                    {{ number_format((float) $inv->branch_bonus, 2) }}
                                </td>

                                <td>
                                    <span class="cell-truncate" title="{{ $inv->remarks }}">
                                        {{ $inv->remarks }}
                                    </span>
                                </td>

                                <td>{{ $inv->po_number }}</td>

                                <td class="row-actions">
                                    <button type="button" class="btn btn--sm btn--text btn--danger" title="Delete"
                                        data-invoice-delete="{{ route('invoices.destroy', $inv) }}"
                                        data-invoice-name="{{ $inv->name ?: $inv->sid ?: $inv->application_id }}"
                                        data-invoice-id="{{ $inv->application_id }}">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="pager">
        <span>{{ number_format($invoices->total()) }} rows</span>
        {{ $invoices->links() }}
    </div>

    {{-- Delete form kept outside the table to avoid nested forms --}}
    <form method="POST" id="invoiceDeleteForm" style="display:none">
        @csrf
        @method('DELETE')
    </form>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const primaryColor = '#1a73e8';
            const dangerColor = '#d93025';
            const cancelColor = '#6b7280';

            /*
            |--------------------------------------------------------------------------
            | Laravel flash messages
            |--------------------------------------------------------------------------
            */

            @if (session('status'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: @json(session('status')),
                    confirmButtonText: 'OK',
                    confirmButtonColor: primaryColor
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Unable to continue',
                    text: @json(session('error')),
                    confirmButtonText: 'OK',
                    confirmButtonColor: primaryColor
                });
            @endif

            @if ($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Please check the form',
                    html: `
                        <div style="text-align:left">
                            <ul style="margin:0; padding-left:20px">
                                @foreach ($errors->all() as $error)
                                    <li>{{ addslashes($error) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    `,
                    confirmButtonText: 'OK',
                    confirmButtonColor: primaryColor
                });
            @endif

            /*
            |--------------------------------------------------------------------------
            | Delete one invoice
            |--------------------------------------------------------------------------
            */

            document.querySelectorAll('[data-invoice-delete]').forEach(function(button) {
                button.addEventListener('click', function() {
                    const invoiceName = button.dataset.invoiceName || 'this invoice';
                    const applicationId = button.dataset.invoiceId || '';

                    Swal.fire({
                        icon: 'warning',
                        title: 'Delete invoice row?',
                        html: `
                            <div style="text-align:center">
                                <p style="margin:0">
                                    Delete invoice for
                                    <strong>${escapeHtml(invoiceName)}</strong>?
                                </p>

                                ${
                                    applicationId
                                        ? `
                                                <p style="margin:8px 0 0; color:#6b7280; font-size:14px">
                                                    Application ID:
                                                    <strong>${escapeHtml(applicationId)}</strong>
                                                </p>
                                            `
                                        : ''
                                }

                                <p style="margin:14px 0 0; color:#6b7280; font-size:14px">
                                    The student's paid fee and commission intake figures
                                    will be refreshed after deletion.
                                </p>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: dangerColor,
                        cancelButtonColor: cancelColor,
                        reverseButtons: true,
                        focusCancel: true
                    }).then(function(result) {
                        if (!result.isConfirmed) {
                            return;
                        }

                        Swal.fire({
                            title: 'Deleting invoice...',
                            text: 'Please wait.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function() {
                                Swal.showLoading();
                            }
                        });

                        const form = document.getElementById('invoiceDeleteForm');
                        form.action = button.dataset.invoiceDelete;
                        form.submit();
                    });
                });
            });

            /*
            |--------------------------------------------------------------------------
            | Optional: delete all invoices
            |--------------------------------------------------------------------------
            |
            | This code works automatically if deleteAllInvoicesForm is uncommented.
            |
            */

            const deleteAllInvoicesForm = document.getElementById('deleteAllInvoicesForm');

            deleteAllInvoicesForm?.addEventListener('submit', function(event) {
                event.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'Delete all invoice rows?',
                    html: `
                        <p style="margin:0">
                            This will permanently delete every invoice row.
                        </p>
                        <p style="margin:12px 0 0; color:#6b7280; font-size:14px">
                            Paid fee and all intake columns will be recalculated
                            for every student.
                        </p>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete all',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: dangerColor,
                    cancelButtonColor: cancelColor,
                    reverseButtons: true,
                    focusCancel: true
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    Swal.fire({
                        title: 'Deleting all invoices...',
                        text: 'Please wait while student values are recalculated.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: function() {
                            Swal.showLoading();
                        }
                    });

                    deleteAllInvoicesForm.submit();
                });
            });

            /*
            |--------------------------------------------------------------------------
            | Optional: clear all system tables
            |--------------------------------------------------------------------------
            |
            | This code works automatically if freshSystemForm is uncommented.
            |
            */

            const freshSystemForm = document.getElementById('freshSystemForm');

            freshSystemForm?.addEventListener('submit', function(event) {
                event.preventDefault();

                Swal.fire({
                    icon: 'error',
                    title: 'Clear all tables?',
                    html: `
                        <p style="margin:0">
                            This runs <strong>migrate:fresh --seed</strong>.
                        </p>
                        <p style="margin:12px 0 0; color:#d93025; font-size:14px">
                            All database tables and imported data will be permanently cleared.
                        </p>
                    `,
                    input: 'text',
                    inputLabel: 'Type DELETE ALL to confirm',
                    inputPlaceholder: 'DELETE ALL',
                    showCancelButton: true,
                    confirmButtonText: 'Clear all tables',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: dangerColor,
                    cancelButtonColor: cancelColor,
                    reverseButtons: true,
                    focusCancel: true,
                    preConfirm: function(value) {
                        if (value !== 'DELETE ALL') {
                            Swal.showValidationMessage('Please type DELETE ALL exactly.');
                            return false;
                        }

                        return true;
                    }
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    Swal.fire({
                        title: 'Clearing database...',
                        text: 'Please wait. Do not close this page.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: function() {
                            Swal.showLoading();
                        }
                    });

                    freshSystemForm.submit();
                });
            });

            function escapeHtml(value) {
                const element = document.createElement('div');
                element.textContent = value ?? '';
                return element.innerHTML;
            }
        });
    </script>
@endsection
