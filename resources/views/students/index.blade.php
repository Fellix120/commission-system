@extends('layouts.app')
@section('title', 'Student Details - Commission Dashboard')

@section('content')
    @php $archived = request()->boolean('archived'); @endphp

    <div class="page-head">
        <h1>{{ $archived ? 'Archived students' : 'Student Details' }}</h1>
        <span class="badge badge--grey">{{ $students->total() }} rows</span>
        @if (!$archived)
            <span class="card__sub">
                Value-pasted &middot;
                last refresh
                {{ optional(\App\Models\Student::max('values_refreshed_at'))
                    ? \Carbon\Carbon::parse(\App\Models\Student::max('values_refreshed_at'))->diffForHumans()
                    : 'never' }}
            </span>
        @endif

        <div class="page-head__actions">
            <a href="{{ route('export.students', request()->query()) }}" class="btn btn--outlined">
                <span class="material-symbols-outlined">download</span> Export
            </a>
            @unless ($archived)
                {{-- <form method="POST" action="{{ route('students.refresh') }}" id="refreshStudentsForm">
                    @csrf
                    <button class="btn btn--filled">
                        <span class="material-symbols-outlined">refresh</span> Refresh values
                    </button>
                </form> --}}
                <form method="POST" action="{{ route('students.refresh') }}">
                    @csrf

                    <button type="submit" class="btn btn--filled">
                        <span class="material-symbols-outlined">refresh</span>
                        Refresh values
                    </button>
                </form>
            @endunless
            {{-- <form method="POST" action="{{ route('system.fresh') }}"
                  data-confirm="This runs migrate:fresh --seed and permanently clears ALL tables. Continue?">@csrf
                  <button class="btn btn--outlined btn--danger"><span class="material-symbols-outlined">delete_forever</span>
                      Clear all tables</button>
              </form> --}}
        </div>
    </div>

    {{-- Filters --}}
    @include('partials.data-filters', [
        'partners' => $partners,
        'branches' => $branches,
        'intakes' => $intakes,
        'courses' => $courses,
        'keepArchived' => $archived,
        'clearUrl' => route('students.index', ['archived' => $archived ? 1 : null]),
    ])

    @if ($students->isEmpty())
        <div class="card empty">
            <span class="material-symbols-outlined">inbox</span>
            <h3>No students to show</h3>
            <p>Import the workbook, then hit <strong>Refresh values</strong> to build this sheet.</p>
            <p style="margin-top:16px"><a href="{{ route('import.index') }}" class="btn btn--tonal">
                    <span class="material-symbols-outlined">upload_file</span> Import workbook</a></p>
        </div>
    @else
        <form method="POST" action="{{ route('students.bulk-archive') }}" id="bulkForm">
            @csrf

            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                @unless ($archived)
                                    <th class="col-sticky" style="width:36px">
                                        <input type="checkbox"
                                            onclick="document.querySelectorAll('.rowcheck').forEach(c=>c.checked=this.checked)">
                                    </th>
                                @endunless
                                <th class="col-sticky" style="{{ $archived ? '' : 'left:36px' }}">Name</th>
                                <th>Branch</th>
                                <th>Applied Intake</th>
                                <th>Contact ID</th>
                                <th>Partner Name</th>
                                <th>Client ID-CRM</th>
                                <th>Product Name</th>
                                <th>Start</th>
                                <th>End</th>
                                <th class="is-num">Duration</th>
                                <th class="is-num">Fee Total</th>
                                <th class="is-num">Credit Fee</th>
                                <th class="is-num">Bonus Due</th>
                                <th class="is-num">Paid Fee</th>
                                <th class="is-num">Paid Bonus</th>
                                <th class="is-num">Rem. Fee</th>
                                <th class="is-num">Rem. Bonus</th>
                                {{-- Requirement 2: one column per intake, auto-discovered --}}
                                @foreach ($intakeColumns as $code)
                                    <th class="is-num col-intake">{{ $code }}</th>
                                @endforeach
                                <th>HO Remarks</th>
                                <th>Branch Remarks</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $s)
                                <tr class="{{ $s->is_archived ? 'is-archived' : '' }}">
                                    @unless ($archived)
                                        <td class="col-sticky">
                                            <input type="checkbox" class="rowcheck" name="student_ids[]"
                                                value="{{ $s->id }}">
                                        </td>
                                    @endunless
                                    <td class="col-sticky" style="{{ $archived ? '' : 'left:36px' }}">
                                        <a href="{{ route('students.show', $s) }}"
                                            style="color:var(--g-blue); text-decoration:none; font-weight:500">
                                            {{ $s->name }}
                                        </a>
                                    </td>
                                    <td>{{ $s->branch }}</td>
                                    <td>{{ $s->applied_intake_date }}</td>
                                    <td>{{ $s->contact_id }}</td>
                                    <td><span class="cell-truncate">{{ $s->partner_name }}</span></td>
                                    <td>{{ $s->client_id_crm }}</td>
                                    <td><span class="cell-truncate"
                                            title="{{ $s->product_name }}">{{ $s->product_name }}</span></td>
                                    <td>{{ $s->start_date?->format('d/m/Y') }}</td>
                                    <td>{{ $s->end_date?->format('d/m/Y') }}</td>
                                    <td class="is-num">{{ number_format((float) $s->course_duration, 2) }}</td>
                                    <td class="is-money">{{ number_format((float) $s->fee_total, 0) }}</td>
                                    <td class="is-money">{{ number_format((float) $s->credit_fee, 0) }}</td>
                                    <td class="is-money">
                                        {{ $s->bonus_due !== null ? number_format((float) $s->bonus_due, 0) : '–' }}</td>
                                    <td class="is-money">{{ number_format((float) $s->paid_fee, 0) }}</td>
                                    <td class="is-money">{{ number_format((float) $s->paid_bonus, 0) }}</td>
                                    <td class="is-money">
                                        @if ((float) $s->remaining_fee > 0)
                                            <span
                                                style="color:var(--g-red)">{{ number_format((float) $s->remaining_fee, 0) }}</span>
                                        @else
                                            <span style="color:var(--g-green)">0</span>
                                        @endif
                                    </td>
                                    <td class="is-money">{{ number_format((float) $s->remaining_bonus, 0) }}</td>
                                    @foreach ($intakeColumns as $code)
                                        @php $amt = $s->receivedFor($code); @endphp
                                        <td class="is-money col-intake">
                                            {{ $amt > 0 ? number_format($amt, 0) : '–' }}
                                        </td>
                                    @endforeach
                                    <td><span class="cell-truncate">{{ $s->ho_remarks }}</span></td>
                                    <td><span class="cell-truncate">{{ $s->branch_remarks }}</span></td>
                                    <td class="row-actions">
                                        <a href="{{ route('students.show', $s) }}" class="btn btn--sm btn--text"
                                            title="Open">
                                            <span class="material-symbols-outlined">open_in_new</span>
                                        </a>
                                        @if ($s->is_archived)
                                            <button type="submit" class="btn btn--sm btn--text js-student-action"
                                                formaction="{{ route('students.unarchive', $s) }}" formmethod="POST"
                                                data-action-type="restore" data-student-name="{{ $s->name }}"
                                                title="Restore">
                                                <span class="material-symbols-outlined">unarchive</span>
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn--sm btn--text js-student-action"
                                                formaction="{{ route('students.archive', $s) }}" formmethod="POST"
                                                data-action-type="archive" data-student-name="{{ $s->name }}"
                                                data-is-settled="{{ $s->isSettled() ? '1' : '0' }}"
                                                title="{{ $s->isSettled() ? 'Archive' : 'Archive (outstanding balance)' }}">
                                                <span class="material-symbols-outlined">archive</span>
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn--sm btn--text btn--danger" title="Delete"
                                            data-row-delete="{{ route('students.destroy', $s) }}"
                                            data-row-name="{{ $s->name }}">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @unless ($archived)
                <div class="chips" style="margin-top:12px">
                    <button type="submit" class="btn btn--outlined" id="bulkArchiveButton">
                        <span class="material-symbols-outlined">archive</span> Archive selected (settled only)
                    </button>
                    <button type="submit" class="btn btn--outlined btn--danger" id="bulkDeleteButton"
                        formaction="{{ route('students.bulk-delete') }}" formmethod="POST">
                        <span class="material-symbols-outlined">delete</span> Delete selected
                    </button>
                    <label class="chip">
                        <input type="checkbox" name="force" value="1" style="margin-right:6px">
                        Archive anyway, even with a balance
                    </label>
                </div>
            @endunless

        </form>
    @endif

    <div class="pager">
        <span>Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }}</span>
        {{ $students->links() }}
    </div>

    {{-- Row deletes post here. Kept outside the bulk form above, since nesting
       forms is invalid HTML and browsers silently drop the inner one. --}}
    <form method="POST" id="rowDeleteForm" style="display:none">
        @csrf
        @method('DELETE')
    </form>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const swalTheme = {
                confirmButtonColor: '#1a73e8',
                cancelButtonColor: '#6b7280',
                reverseButtons: true,
                focusCancel: true
            };

            function selectedStudentCount() {
                return document.querySelectorAll('.rowcheck:checked').length;
            }

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
                    confirmButtonColor: swalTheme.confirmButtonColor
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Unable to continue',
                    text: @json(session('error')),
                    confirmButtonColor: swalTheme.confirmButtonColor
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
                    confirmButtonColor: swalTheme.confirmButtonColor
                });
            @endif

            /*
            |--------------------------------------------------------------------------
            | Refresh Student Details
            |--------------------------------------------------------------------------
            */

            const refreshForm = document.getElementById('refreshStudentsForm');

            refreshForm?.addEventListener('submit', function(event) {
                event.preventDefault();

                Swal.fire({
                    icon: 'question',
                    title: 'Refresh Student Details?',
                    html: `
                          <p style="margin:0">
                              Recalculate Student Details from
                              <strong>CRM Data + Invoice</strong>?
                          </p>
                          <p style="margin:12px 0 0; color:#6b7280; font-size:14px">
                              Archived students will be left untouched.
                          </p>
                      `,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, refresh values',
                    cancelButtonText: 'Cancel',
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading(),
                    ...swalTheme
                }).then(function(result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Refreshing values...',
                            text: 'Please wait while Student Details is rebuilt.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => Swal.showLoading()
                        });

                        refreshForm.submit();
                    }
                });
            });

            /*
            |--------------------------------------------------------------------------
            | Archive or restore one student
            |--------------------------------------------------------------------------
            */

            document.querySelectorAll('.js-student-action').forEach(function(button) {
                button.addEventListener('click', function(event) {
                    event.preventDefault();

                    const form = button.closest('form');
                    const actionType = button.dataset.actionType;
                    const studentName = button.dataset.studentName || 'this student';
                    const isSettled = button.dataset.isSettled === '1';

                    let title;
                    let text;
                    let icon;
                    let confirmText;

                    if (actionType === 'restore') {
                        title = 'Restore student?';
                        text = studentName + ' will return to the active Student Details list.';
                        icon = 'question';
                        confirmText = 'Yes, restore';
                    } else {
                        title = isSettled ?
                            'Archive student?' :
                            'Archive student with outstanding balance?';

                        text = isSettled ?
                            studentName + ' will be moved to archived students.' :
                            studentName +
                            ' still has an outstanding fee or bonus. The server may reject this unless archive anyway is enabled.';

                        icon = isSettled ? 'question' : 'warning';
                        confirmText = 'Yes, archive';
                    }

                    Swal.fire({
                        icon: icon,
                        title: title,
                        text: text,
                        showCancelButton: true,
                        confirmButtonText: confirmText,
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: actionType === 'restore' ?
                            '#1a73e8' : '#f59e0b',
                        cancelButtonColor: swalTheme.cancelButtonColor,
                        reverseButtons: true,
                        focusCancel: true
                    }).then(function(result) {
                        if (!result.isConfirmed) {
                            return;
                        }

                        form.action = button.formAction;
                        form.method = 'POST';
                        form.submit();
                    });
                });
            });

            /*
            |--------------------------------------------------------------------------
            | Delete one student
            |--------------------------------------------------------------------------
            */

            document.querySelectorAll('[data-row-delete]').forEach(function(button) {
                button.addEventListener('click', function() {
                    const studentName = button.dataset.rowName || 'this student';

                    Swal.fire({
                        icon: 'warning',
                        title: 'Delete student?',
                        html: `
                              <p style="margin:0">
                                  Delete <strong>${escapeHtml(studentName)}</strong>?
                              </p>
                              <p style="margin:12px 0 0; color:#6b7280; font-size:14px">
                                  A future refresh can rebuild this row from CRM Data.
                              </p>
                          `,
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#d93025',
                        cancelButtonColor: swalTheme.cancelButtonColor,
                        reverseButtons: true,
                        focusCancel: true
                    }).then(function(result) {
                        if (!result.isConfirmed) {
                            return;
                        }

                        const form = document.getElementById('rowDeleteForm');
                        form.action = button.dataset.rowDelete;
                        form.submit();
                    });
                });
            });

            /*
            |--------------------------------------------------------------------------
            | Bulk archive and bulk delete
            |--------------------------------------------------------------------------
            */

            const bulkForm = document.getElementById('bulkForm');
            const bulkArchiveButton = document.getElementById('bulkArchiveButton');
            const bulkDeleteButton = document.getElementById('bulkDeleteButton');

            bulkArchiveButton?.addEventListener('click', function(event) {
                event.preventDefault();

                const count = selectedStudentCount();

                if (count === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'No students selected',
                        text: 'Select at least one student to archive.',
                        confirmButtonColor: swalTheme.confirmButtonColor
                    });
                    return;
                }

                const forceArchive = bulkForm.querySelector('input[name="force"]')?.checked;

                Swal.fire({
                    icon: forceArchive ? 'warning' : 'question',
                    title: 'Archive selected students?',
                    html: `
                          <p style="margin:0">
                              Archive <strong>${count}</strong>
                              selected student${count === 1 ? '' : 's'}?
                          </p>
                          <p style="margin:12px 0 0; color:#6b7280; font-size:14px">
                              ${
                                  forceArchive
                                      ? 'Students will be archived even when they have an outstanding balance.'
                                      : 'Only settled students will be archived.'
                              }
                          </p>
                      `,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, archive selected',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: swalTheme.cancelButtonColor,
                    reverseButtons: true,
                    focusCancel: true
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    bulkForm.action = bulkForm.getAttribute('action');
                    bulkForm.method = 'POST';
                    bulkForm.submit();
                });
            });

            bulkDeleteButton?.addEventListener('click', function(event) {
                event.preventDefault();

                const count = selectedStudentCount();

                if (count === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'No students selected',
                        text: 'Select at least one student to delete.',
                        confirmButtonColor: swalTheme.confirmButtonColor
                    });
                    return;
                }

                Swal.fire({
                    icon: 'warning',
                    title: 'Delete selected students?',
                    html: `
                          <p style="margin:0">
                              Permanently delete <strong>${count}</strong>
                              selected student${count === 1 ? '' : 's'}?
                          </p>
                          <p style="margin:12px 0 0; color:#6b7280; font-size:14px">
                              A future refresh can rebuild these rows from CRM Data.
                          </p>
                      `,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete selected',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#d93025',
                    cancelButtonColor: swalTheme.cancelButtonColor,
                    reverseButtons: true,
                    focusCancel: true
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    bulkForm.action = bulkDeleteButton.formAction;
                    bulkForm.method = 'POST';
                    bulkForm.submit();
                });
            });

            function escapeHtml(value) {
                const element = document.createElement('div');
                element.textContent = value;
                return element.innerHTML;
            }
        });
    </script>
@endsection
