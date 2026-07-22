@extends('layouts.app')
@section('title', 'Import workbook - Commission Dashboard')

@section('content')
    <div class="page-head">
        <h1>Import workbook</h1>
        <span class="card__sub">Reads {{ implode(', ', $sheets) }} &middot; Student Details is rebuilt, not imported</span>
    </div>

    @if ($stats['last'] && $stats['last']->hasUnderstated())
        <div class="alert">
            <span class="material-symbols-outlined">warning</span>
            <div>
                <strong>The last import found a short export.</strong>
                {{ $stats['last']->understated_note }}
            </div>
        </div>
    @endif

    <div class="import-grid">
        {{-- Upload --}}
        <div class="card card--pad">
            <div class="card__title" style="margin-bottom:6px">Upload</div>
            <div class="card__sub" style="margin-bottom:16px">
                An .xlsx export up to 20 MB.
            </div>

            <form method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data" id="uploadForm">
                @csrf

                <label class="dropzone" id="dropzone">
                    <input type="file" name="workbook" accept=".xlsx,.xls" required id="fileInput" hidden>
                    <span class="material-symbols-outlined dropzone__icon">upload_file</span>
                    <span class="dropzone__label" id="dropLabel">Choose a workbook</span>
                    <span class="dropzone__hint">or drag it here</span>
                </label>

                @error('workbook')
                    <div class="field-error">{{ $message }}</div>
                @enderror

                <label class="replace-toggle">
                    <input type="checkbox" name="replace" value="1" id="replaceBox">
                    <span>
                        <strong>Replace existing data</strong>
                        <em>Clears CRM Data, Invoice, Raw Data and every student row first. Leave off to add to what is
                            already there.</em>
                    </span>
                </label>

                <button type="submit" class="btn btn--filled" style=" margin-top:16px">
                    <span class="material-symbols-outlined">publish</span> Import
                </button>
            </form>

            <div class="note">
                <span class="material-symbols-outlined">info</span>
                <div>
                    <strong>Student Details is not read.</strong>
                    That tab is a value paste. This app rebuilds it from CRM Data and Invoice, so
                    importing the frozen copy would mean trusting it over its own source.
                    Archived students keep their figures either way.
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div>
            <div class="stats" style="margin-bottom:16px">
                <div class="stat">
                    <div class="stat__label">Imports</div>
                    <div class="stat__value">{{ $stats['total'] }}</div>
                </div>
                <div class="stat stat--green">
                    <div class="stat__label">Completed</div>
                    <div class="stat__value">{{ $stats['completed'] }}</div>
                </div>
                <div class="stat {{ $stats['failed'] ? 'stat--red' : '' }}">
                    <div class="stat__label">Failed</div>
                    <div class="stat__value">{{ $stats['failed'] }}</div>
                </div>
            </div>

            @if ($stats['last'])
                <div class="card card--pad">
                    <div class="card__title" style="margin-bottom:12px">Last successful import</div>
                    <div class="last-grid">
                        <span>File</span><strong>{{ $stats['last']->filename }}</strong>
                        <span>When</span><strong>{{ $stats['last']->created_at->diffForHumans() }}</strong>
                        <span>CRM rows</span><strong>{{ number_format($stats['last']->crm_rows) }}</strong>
                        <span>Invoice rows</span><strong>{{ number_format($stats['last']->invoice_rows) }}</strong>
                        <span>Students built</span><strong>{{ number_format($stats['last']->students_built) }}</strong>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- History --}}
    <div class="page-head" style="margin-top:28px">
        <h1 style="font-size:18px">History</h1>
        <span class="badge badge--grey">{{ $imports->total() }}</span>
        <div class="page-actions">
            @if ($stats['failed'] > 0)
                <form method="POST" action="{{ route('import.clear-failed') }}"
                    data-confirm="Clear the {{ $stats['failed'] }} failed import{{ $stats['failed'] === 1 ? '' : 's' }} from the history?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn--outlined">
                        <span class="material-symbols-outlined">error</span> Clear failed
                    </button>
                </form>
            @endif
            {{-- <form method="POST" action="{{ route('import.clear') }}"
                data-confirm="Clear the entire import history? This removes the log only – the imported rows stay in place.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--outlined btn--danger" {{ $imports->total() ? '' : 'disabled' }}>
                    <span class="material-symbols-outlined">delete_sweep</span> Clear all
                </button>
            </form> --}}
            <form method="POST" action="{{ route('system.clear-imported') }}"
                data-confirm="This deletes every raw data, CRM, invoice and student row plus the import history. Targets are kept. This cannot be undone."
                data-confirm-title="Clear all imported data?" data-confirm-button="Yes, clear everything">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--outlined btn--danger">
                    <span class="material-symbols-outlined">delete_forever</span> Clear all imported data
                </button>
            </form>
        </div>
    </div>

    <form method="GET" class="chips">
        <a href="{{ route('import.index') }}" class="chip {{ request('status') ? '' : 'is-active' }}">All</a>
        <a href="{{ route('import.index', ['status' => 'completed']) }}"
            class="chip {{ request('status') === 'completed' ? 'is-active' : '' }}">Completed</a>
        <a href="{{ route('import.index', ['status' => 'failed']) }}"
            class="chip {{ request('status') === 'failed' ? 'is-active' : '' }}">Failed</a>
    </form>

    @if ($imports->isEmpty())
        <div class="card empty">
            <span class="material-symbols-outlined">history</span>
            <h3>No imports yet</h3>
            <p>Upload a workbook and every run will be logged here.</p>
        </div>
    @else
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="col-sticky">File</th>
                            <th>When</th>
                            <th>Status</th>
                            <th>Sheets</th>
                            <th class="is-num">Raw</th>
                            <th class="is-num">CRM</th>
                            <th class="is-num">Invoice</th>
                            <th class="is-num">Students</th>
                            <th class="is-num">Size</th>
                            <th class="is-num">Took</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($imports as $import)
                            <tr>
                                <td class="col-sticky">
                                    <div style="font-weight:500">{{ $import->filename }}</div>
                                    @if ($import->replaced_existing)
                                        <span class="badge badge--yellow">replaced existing</span>
                                    @endif
                                    @if ($import->isFailed() && $import->error)
                                        <div class="err">{{ $import->error }}</div>
                                    @endif
                                    @if ($import->hasUnderstated())
                                        <div class="warn" title="{{ $import->understated_note }}">
                                            <span class="material-symbols-outlined">warning</span>
                                            {{ $import->students_understated }} understated
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $import->created_at->format('d/m/Y H:i') }}</div>
                                    <div class="sub">{{ $import->created_at->diffForHumans() }}</div>
                                </td>
                                <td>
                                    @if ($import->status === 'completed')
                                        <span class="badge badge--green">completed</span>
                                    @elseif ($import->isFailed())
                                        <span class="badge badge--red">failed</span>
                                    @else
                                        <span class="badge badge--grey">{{ $import->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse ($import->sheets() as $sheet)
                                        <span class="badge badge--blue">{{ $sheet }}</span>
                                    @empty
                                        <span class="sub">–</span>
                                    @endforelse
                                </td>
                                <td class="is-num">{{ $import->raw_rows ?: '–' }}</td>
                                <td class="is-num">{{ $import->crm_rows ?: '–' }}</td>
                                <td class="is-num">{{ $import->invoice_rows ?: '–' }}</td>
                                <td class="is-num">
                                    {{ $import->students_built ?: '–' }}
                                    @if ($import->students_skipped)
                                        <span class="sub" title="Archived students, left frozen">+{{ $import->students_skipped }}
                                            kept</span>
                                    @endif
                                </td>
                                <td class="is-num sub">{{ $import->humanSize() }}</td>
                                <td class="is-num sub">{{ $import->humanDuration() }}</td>
                                {{-- <td>
                                    <form method="POST" action="{{ route('import.destroy', $import) }}" style="display:inline"
                                        data-confirm="Remove {{ $import->filename }} from the history? The rows it imported stay in place.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--sm btn--text btn--danger" title="Remove from history">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
                                </td> --}}
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pager">
            <span>Showing {{ $imports->firstItem() }}&ndash;{{ $imports->lastItem() }} of {{ $imports->total() }}</span>
            {{ $imports->links() }}
        </div>
    @endif

    <div class="note note--wide">
        <span class="material-symbols-outlined">help</span>
        <div>
            <strong>Deleting history does not delete data.</strong>
            An entry is a record of a run. The rows it loaded may since have been edited, and
            later imports may have added to them, so there is no safe way to unpick one run's
            contribution. To clear the data itself use <em>Delete all</em> on the CRM Data,
            Invoice or Raw Data tabs, or import again with <em>Replace existing data</em> ticked.
        </div>
    </div>

    <style>
        .import-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start
        }

        @media (max-width:1000px) {
            .import-grid {
                grid-template-columns: 1fr
            }
        }

        .dropzone {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 2px dashed var(--g-grey-300);
            border-radius: 12px;
            padding: 32px 16px;
            cursor: pointer;
            transition: background .15s, border-color .15s;
            text-align: center
        }

        .dropzone:hover,
        .dropzone.is-over {
            background: var(--g-blue-light);
            border-color: var(--g-blue)
        }

        .dropzone.is-set {
            background: var(--g-green-light);
            border-color: var(--g-green);
            border-style: solid
        }

        .dropzone__icon {
            font-size: 34px;
            color: var(--g-grey-500)
        }

        .dropzone.is-set .dropzone__icon {
            color: var(--g-green)
        }

        .dropzone__label {
            font-weight: 500;
            font-size: 14px;
            word-break: break-all
        }

        .dropzone__hint {
            font-size: 12px;
            color: var(--g-grey-500)
        }

        .dropzone.is-set .dropzone__hint {
            display: none
        }

        .field-error {
            color: var(--g-red);
            font-size: 13px;
            margin-top: 8px
        }

        .replace-toggle {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-top: 16px;
            cursor: pointer
        }

        .replace-toggle input {
            margin-top: 3px
        }

        .replace-toggle em {
            display: block;
            font-style: normal;
            font-size: 12px;
            color: var(--g-grey-700);
            margin-top: 2px;
            line-height: 1.5
        }

        .note {
            display: flex;
            gap: 10px;
            margin-top: 18px;
            padding: 12px;
            background: var(--g-grey-50);
            border-radius: 8px;
            font-size: 12px;
            color: var(--g-grey-700);
            line-height: 1.6
        }

        .note--wide {
            margin-top: 20px
        }

        .note .material-symbols-outlined {
            font-size: 18px;
            color: var(--g-grey-500);
            flex-shrink: 0
        }

        .last-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 8px 16px;
            font-size: 13px;
            align-items: center
        }

        .last-grid span {
            color: var(--g-grey-700)
        }

        .last-grid strong {
            font-weight: 500;
            text-align: right
        }

        .sub {
            font-size: 11px;
            color: var(--g-grey-500)
        }

        .err {
            font-size: 11px;
            color: var(--g-red);
            margin-top: 4px;
            max-width: 280px;
            line-height: 1.5
        }

        .warn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: #b06000;
            background: var(--g-yellow-light);
            border-radius: 8px;
            padding: 1px 7px;
            margin-top: 4px
        }

        .warn .material-symbols-outlined {
            font-size: 12px
        }

        .alert {
            display: flex;
            gap: 10px;
            padding: 12px 14px;
            background: var(--g-yellow-light);
            border: 1px solid #fde293;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 13px;
            color: #856000;
            line-height: 1.6
        }

        .alert .material-symbols-outlined {
            font-size: 19px;
            flex-shrink: 0
        }
    </style>

    <script>
        (function () {
            var zone = document.getElementById('dropzone');
            var input = document.getElementById('fileInput');
            var label = document.getElementById('dropLabel');
            if (!zone || !input) return;

            function show(name) {
                label.textContent = name;
                zone.classList.add('is-set');
            }

            input.addEventListener('change', function () {
                if (input.files.length) show(input.files[0].name);
            });

            ['dragenter', 'dragover'].forEach(function (evt) {
                zone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    zone.classList.add('is-over');
                });
            });

            ['dragleave', 'drop'].forEach(function (evt) {
                zone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    zone.classList.remove('is-over');
                });
            });

            zone.addEventListener('drop', function (e) {
                if (!e.dataTransfer.files.length) return;
                input.files = e.dataTransfer.files;
                show(e.dataTransfer.files[0].name);
            });

            // Replacing wipes everything, so make that an explicit decision.
            var uploadForm = document.getElementById('uploadForm');
            var confirmedReplace = false;

            uploadForm.addEventListener('submit', function (e) {
                if (!document.getElementById('replaceBox').checked || confirmedReplace) return;

                var message = 'CRM Data, Invoice, Raw Data and every student row will be cleared first.';

                if (typeof Swal === 'undefined') {
                    if (!confirm('Replace existing data? ' + message)) e.preventDefault();
                    return;
                }

                e.preventDefault();

                Swal.fire({
                    title: 'Replace existing data?',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, replace it',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#d93025',
                    reverseButtons: true,
                    focusCancel: true
                }).then(function (result) {
                    if (result.isConfirmed) {
                        // requestSubmit re-fires the submit event; the flag
                        // lets it through this time.
                        confirmedReplace = true;
                        uploadForm.requestSubmit();
                        confirmedReplace = false;
                    }
                });
            });
        })();
    </script>
@endsection