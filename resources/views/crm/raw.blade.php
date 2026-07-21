@extends('layouts.app')

@section('title', 'Raw Data Export - Commission Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>Raw Data Export</h1>
            <span class="card__sub">
                Untouched CRM dump &middot; stage 1 uses Admission enrolments only; OSHC lands in stage 2
            </span>
        </div>

        <div class="page-actions">
            <a href="{{ route('export.raw', request()->query()) }}" class="btn btn--outlined">
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

            <form method="POST" action="{{ route('crm.raw.truncate') }}" id="deleteAllRawRowsForm">
                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="btn btn--outlined btn--danger"
                    {{ $rows->total() ? '' : 'disabled' }}
                >
                    <span class="material-symbols-outlined">delete_sweep</span>
                    Delete all
                </button>
            </form>
            --}}
        </div>
    </div>

    @include('partials.data-filters', [
        'partners' => $partners,
        'branches' => $branches,
        'intakes' => $intakes,
        'courses' => $courses,
        'clearUrl' => route('crm.raw'),
    ])

    @if ($rows->isEmpty())
        <div class="card empty">
            <span class="material-symbols-outlined">database</span>
            <h3>No raw data rows found</h3>

            @if (request()->hasAny(['search', 'partner', 'branch', 'intake', 'course', 'status']))
                <p>No raw data rows match the selected filters.</p>

                <p style="margin-top:16px">
                    <a href="{{ route('crm.raw') }}" class="btn btn--tonal">
                        <span class="material-symbols-outlined">filter_alt_off</span>
                        Clear filters
                    </a>
                </p>
            @else
                <p>Import a CRM raw export to display records here.</p>
            @endif
        </div>
    @else
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="col-sticky">Client</th>
                            <th>Added</th>
                            <th>Application ID</th>
                            <th>Deal</th>
                            <th>Branch</th>
                            <th>Workflow</th>
                            <th>Partner</th>
                            <th>Product</th>
                            <th>Intake</th>
                            <th>Stage</th>
                            <th>Status</th>
                            <th class="is-num">Fee Total</th>
                            <th>Owner</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($rows as $r)
                            @php
                                $clientName = trim(
                                    (string) $r->client_first_name . ' ' . (string) $r->client_last_name,
                                );
                            @endphp

                            <tr>
                                <td class="col-sticky">
                                    {{ $clientName !== '' ? $clientName : '–' }}
                                </td>

                                <td>{{ $r->added_date?->format('d/m/Y') }}</td>
                                <td>{{ $r->application_id }}</td>

                                <td>
                                    <span class="cell-truncate" title="{{ $r->deal_name }}">
                                        {{ $r->deal_name }}
                                    </span>
                                </td>

                                <td>{{ $r->branch }}</td>

                                <td>
                                    <span class="cell-truncate" title="{{ $r->workflow }}">
                                        {{ $r->workflow }}
                                    </span>
                                </td>

                                <td>
                                    <span class="cell-truncate" title="{{ $r->partner_name }}">
                                        {{ $r->partner_name }}
                                    </span>
                                </td>

                                <td>
                                    <span class="cell-truncate" title="{{ $r->product_name }}">
                                        {{ $r->product_name }}
                                    </span>
                                </td>

                                <td>{{ $r->intake_date }}</td>
                                <td>{{ $r->current_stage }}</td>

                                <td>
                                    <span class="badge {{ $r->status === 'Completed' ? 'badge--green' : 'badge--grey' }}">
                                        {{ $r->status }}
                                    </span>
                                </td>

                                <td class="is-money">
                                    {{ number_format((float) $r->fee_total, 2) }}
                                </td>

                                <td>{{ $r->application_owner }}</td>

                                <td class="row-actions">
                                    <button type="button" class="btn btn--sm btn--text btn--danger" title="Delete"
                                        data-raw-delete="{{ route('crm.raw.destroy', $r) }}"
                                        data-raw-client="{{ $clientName !== '' ? $clientName : 'this client' }}"
                                        data-raw-application-id="{{ $r->application_id }}"
                                        data-raw-deal="{{ $r->deal_name }}">
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
        <span>{{ number_format($rows->total()) }} rows</span>
        {{ $rows->links() }}
    </div>

    {{-- Delete form kept outside the table to avoid nested forms --}}
    <form method="POST" id="rawDeleteForm" style="display:none">
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
            | Delete one raw row
            |--------------------------------------------------------------------------
            */

            document.querySelectorAll('[data-raw-delete]').forEach(function(button) {
                button.addEventListener('click', function() {
                    const clientName = button.dataset.rawClient || 'this client';
                    const applicationId = button.dataset.rawApplicationId || '';
                    const dealName = button.dataset.rawDeal || '';

                    Swal.fire({
                        icon: 'warning',
                        title: 'Delete raw data row?',
                        html: `
                            <div style="text-align:center">
                                <p style="margin:0">
                                    Delete the raw row for
                                    <strong>${escapeHtml(clientName)}</strong>?
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

                                ${
                                    dealName
                                        ? `
                                                <p style="margin:6px 0 0; color:#6b7280; font-size:14px">
                                                    Deal:
                                                    <strong>${escapeHtml(dealName)}</strong>
                                                </p>
                                            `
                                        : ''
                                }

                                <p style="margin:14px 0 0; color:#6b7280; font-size:14px">
                                    Nothing is derived from Raw Data Export, so Student Details,
                                    CRM Data and Invoice figures will not change.
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
                            title: 'Deleting raw row...',
                            text: 'Please wait.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function() {
                                Swal.showLoading();
                            }
                        });

                        const form = document.getElementById('rawDeleteForm');
                        form.action = button.dataset.rawDelete;
                        form.submit();
                    });
                });
            });

            /*
            |--------------------------------------------------------------------------
            | Optional: delete all raw rows
            |--------------------------------------------------------------------------
            |
            | This works automatically if deleteAllRawRowsForm is uncommented.
            |
            */

            const deleteAllRawRowsForm = document.getElementById('deleteAllRawRowsForm');

            deleteAllRawRowsForm?.addEventListener('submit', function(event) {
                event.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'Delete all raw data rows?',
                    html: `
                        <p style="margin:0">
                            This will permanently delete every Raw Data Export row.
                        </p>
                        <p style="margin:12px 0 0; color:#6b7280; font-size:14px">
                            Nothing is derived from this tab, so calculated figures will not change.
                        </p>
                    `,
                    input: 'text',
                    inputLabel: 'Type DELETE RAW to confirm',
                    inputPlaceholder: 'DELETE RAW',
                    showCancelButton: true,
                    confirmButtonText: 'Delete all raw rows',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: dangerColor,
                    cancelButtonColor: cancelColor,
                    reverseButtons: true,
                    focusCancel: true,
                    preConfirm: function(value) {
                        if (value !== 'DELETE RAW') {
                            Swal.showValidationMessage('Please type DELETE RAW exactly.');
                            return false;
                        }

                        return true;
                    }
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    Swal.fire({
                        title: 'Deleting all raw rows...',
                        text: 'Please wait.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: function() {
                            Swal.showLoading();
                        }
                    });

                    deleteAllRawRowsForm.submit();
                });
            });

            /*
            |--------------------------------------------------------------------------
            | Optional: clear all system tables
            |--------------------------------------------------------------------------
            |
            | This works automatically if freshSystemForm is uncommented.
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
                            All raw data, CRM data, invoices, students and targets
                            will be permanently cleared.
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
