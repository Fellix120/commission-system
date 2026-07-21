@extends('layouts.app')

@section('title', 'CRM Data - Commission Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>CRM Data</h1>
            <span class="card__sub">
                Arranged from the raw export &middot; only &ldquo;Completed&rdquo; feeds commission &middot;
                sorted old &rarr; new
            </span>
        </div>

        <div class="page-actions">
            <a href="{{ route('export.crm', request()->query()) }}" class="btn btn--outlined">
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

            <form method="POST" action="{{ route('crm.truncate') }}" id="deleteAllCrmForm">
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
        'statuses' => $statuses,
        'clearUrl' => route('crm.index'),
    ])

    @if ($rows->isEmpty())
        <div class="card empty">
            <span class="material-symbols-outlined">database</span>
            <h3>No CRM rows found</h3>

            @if (request()->hasAny(['search', 'partner', 'branch', 'intake', 'course', 'status']))
                <p>No CRM rows match the selected filters.</p>

                <p style="margin-top:16px">
                    <a href="{{ route('crm.index') }}" class="btn btn--tonal">
                        <span class="material-symbols-outlined">filter_alt_off</span>
                        Clear filters
                    </a>
                </p>
            @else
                <p>Import CRM data to display records here.</p>
            @endif
        </div>
    @else
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="col-sticky">Name</th>
                            <th>Branch</th>
                            <th>Applied Intake</th>
                            <th>Contact ID</th>
                            <th>Partner</th>
                            <th>Client ID</th>
                            <th>Product</th>
                            <th>Start</th>
                            <th>End</th>
                            <th class="is-num">Fee Total</th>
                            <th>Application ID</th>
                            <th>Status</th>
                            <th>Workflow</th>
                            <th>Stage</th>
                            <th>Product Type</th>
                            <th>Owner</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td class="col-sticky">{{ $r->name }}</td>
                                <td>{{ $r->branch }}</td>
                                <td>{{ $r->applied_intake_date }}</td>
                                <td>{{ $r->contact_id }}</td>

                                <td>
                                    <span class="cell-truncate" title="{{ $r->partner_name }}">
                                        {{ $r->partner_name }}
                                    </span>
                                </td>

                                <td>{{ $r->client_id }}</td>

                                <td>
                                    <span class="cell-truncate" title="{{ $r->product_name }}">
                                        {{ $r->product_name }}
                                    </span>
                                </td>

                                <td>{{ $r->start_date?->format('d/m/Y') }}</td>
                                <td>{{ $r->end_date?->format('d/m/Y') }}</td>

                                <td class="is-money">
                                    {{ number_format((float) $r->fee_total, 2) }}
                                </td>

                                <td>{{ $r->application_id }}</td>

                                <td>
                                    <span class="badge {{ $r->status === 'Completed' ? 'badge--green' : 'badge--grey' }}">
                                        {{ $r->status }}
                                    </span>
                                </td>

                                <td>
                                    <span class="cell-truncate" title="{{ $r->workflow }}">
                                        {{ $r->workflow }}
                                    </span>
                                </td>

                                <td>{{ $r->current_stage }}</td>
                                <td>{{ $r->product_type }}</td>
                                <td>{{ $r->application_owner }}</td>

                                <td class="row-actions">
                                    <button type="button" class="btn btn--sm btn--text btn--danger" title="Delete"
                                        data-crm-delete="{{ route('crm.destroy', $r) }}"
                                        data-crm-name="{{ $r->name ?: $r->client_id ?: $r->application_id }}"
                                        data-crm-client-id="{{ $r->client_id }}"
                                        data-crm-application-id="{{ $r->application_id }}">
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
    <form method="POST" id="crmDeleteForm" style="display:none">
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
            | Delete one CRM row
            |--------------------------------------------------------------------------
            */

            document.querySelectorAll('[data-crm-delete]').forEach(function(button) {
                button.addEventListener('click', function() {
                    const studentName = button.dataset.crmName || 'this CRM record';
                    const clientId = button.dataset.crmClientId || '';
                    const applicationId = button.dataset.crmApplicationId || '';

                    Swal.fire({
                        icon: 'warning',
                        title: 'Delete CRM row?',
                        html: `
                            <div style="text-align:center">
                                <p style="margin:0">
                                    Delete CRM record for
                                    <strong>${escapeHtml(studentName)}</strong>?
                                </p>

                                ${
                                    clientId
                                        ? `
                                                <p style="margin:8px 0 0; color:#6b7280; font-size:14px">
                                                    Client ID:
                                                    <strong>${escapeHtml(clientId)}</strong>
                                                </p>
                                            `
                                        : ''
                                }

                                ${
                                    applicationId
                                        ? `
                                                <p style="margin:6px 0 0; color:#6b7280; font-size:14px">
                                                    Application ID:
                                                    <strong>${escapeHtml(applicationId)}</strong>
                                                </p>
                                            `
                                        : ''
                                }

                                <p style="margin:14px 0 0; color:#6b7280; font-size:14px">
                                    Student Details is built from CRM Data, so the student's figures
                                    will be recalculated after deletion.
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
                            title: 'Deleting CRM row...',
                            text: 'Please wait while Student Details is recalculated.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function() {
                                Swal.showLoading();
                            }
                        });

                        const form = document.getElementById('crmDeleteForm');
                        form.action = button.dataset.crmDelete;
                        form.submit();
                    });
                });
            });

            /*
            |--------------------------------------------------------------------------
            | Optional: delete all CRM rows
            |--------------------------------------------------------------------------
            |
            | This works automatically if deleteAllCrmForm is uncommented above.
            |
            */

            const deleteAllCrmForm = document.getElementById('deleteAllCrmForm');

            deleteAllCrmForm?.addEventListener('submit', function(event) {
                event.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'Delete all CRM rows?',
                    html: `
                        <p style="margin:0">
                            This will permanently delete every CRM row.
                        </p>
                        <p style="margin:12px 0 0; color:#d93025; font-size:14px">
                            Student Details is built from CRM Data, so every student row
                            may also be removed or rebuilt.
                        </p>
                    `,
                    input: 'text',
                    inputLabel: 'Type DELETE CRM to confirm',
                    inputPlaceholder: 'DELETE CRM',
                    showCancelButton: true,
                    confirmButtonText: 'Delete all CRM rows',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: dangerColor,
                    cancelButtonColor: cancelColor,
                    reverseButtons: true,
                    focusCancel: true,
                    preConfirm: function(value) {
                        if (value !== 'DELETE CRM') {
                            Swal.showValidationMessage('Please type DELETE CRM exactly.');
                            return false;
                        }

                        return true;
                    }
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    Swal.fire({
                        title: 'Deleting all CRM rows...',
                        text: 'Please wait while Student Details is updated.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: function() {
                            Swal.showLoading();
                        }
                    });

                    deleteAllCrmForm.submit();
                });
            });

            /*
            |--------------------------------------------------------------------------
            | Optional: clear all system tables
            |--------------------------------------------------------------------------
            |
            | This works automatically if freshSystemForm is uncommented above.
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
                            All tables, CRM rows, invoices and student values
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
