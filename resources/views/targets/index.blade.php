@extends('layouts.app')

@section('title', 'Targets - Commission Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>Target vs enrolment</h1>
            <span class="card__sub">
                Targets supplied by the university or college, compared against actual active enrolments
            </span>
        </div>

        {{-- <span class="badge badge--grey">
            {{ number_format($comparison->count()) }} target{{ $comparison->count() === 1 ? '' : 's' }}
        </span> --}}
    </div>



    {{-- Add/update target --}}
    <div class="card card--pad" style="margin-top:20px; margin-bottom:20px">
        <div class="card__head">
            <span class="material-symbols-outlined" style="color:var(--g-blue)">track_changes</span>

            <div>
                <div class="card__title">Add or update a target</div>
                <div class="card__sub">
                    Saving the same partner, intake and branch updates the existing target.
                </div>
            </div>
        </div>
        <div class="mt-3" style="margin-top: 10px"></div>

        <form method="POST" action="{{ route('targets.store') }}">
            @csrf

            {{-- Preserve active page filters after saving --}}
            @foreach (['search', 'partner', 'branch', 'intake', 'course'] as $filterName)
                @if (request()->filled($filterName))
                    <input type="hidden" name="{{ $filterName }}" value="{{ request($filterName) }}">
                @endif
            @endforeach

            <div class="target-form-grid">
                <div class="field">
                    <label for="partner_name">Partner name <span class="require">*</span></label>

                    <select id="partner_name" name="partner_name" class="searchable-select"
                        data-placeholder="Select partner" required>
                        <option value="">Select partner</option>

                        @foreach ($partners as $partner)
                            <option value="{{ $partner }}" @selected(old('partner_name') === $partner)>
                                {{ $partner }}
                            </option>
                        @endforeach
                    </select>

                    @error('partner_name')
                        <div class="field__error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="intake_code">Intake<span class="require">*</span></label>

                    <select id="intake_code" name="intake_code" class="searchable-select" data-placeholder="Select intake"
                        required>
                        <option value="">Select intake</option>

                        @foreach ($intakes as $intake)
                            <option value="{{ $intake }}" @selected(old('intake_code') === $intake)>
                                {{ $intake }}
                            </option>
                        @endforeach
                    </select>

                    <div class="field__hint">
                        Uses the same value as Student Details → Applied Intake.
                    </div>

                    @error('intake_code')
                        <div class="field__error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="target_branch">Branch</label>

                    <select id="target_branch" name="branch" class="searchable-select" data-placeholder="All branches">
                        <option value="">All branches</option>

                        @foreach ($branches as $branch)
                            <option value="{{ $branch }}" @selected(old('branch') === $branch)>
                                {{ $branch }}
                            </option>
                        @endforeach
                    </select>

                    <div class="field__hint">
                        Leave blank to compare all branches.
                    </div>

                    @error('branch')
                        <div class="field__error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="target_enrolment">Target enrolments<span class="require">*</span></label>

                    <input id="target_enrolment" type="number" name="target_enrolment" required min="0"
                        value="{{ old('target_enrolment') }}">

                    @error('target_enrolment')
                        <div class="field__error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="target_commission">Target commission ($)</label>

                    <input id="target_commission" type="number" step="0.01" name="target_commission" min="0"
                        value="{{ old('target_commission') }}">

                    @error('target_commission')
                        <div class="field__error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="notes">Notes</label>

                    {{-- <input id="notes" type="text" name="notes" maxlength="1000" value="{{ old('notes') }}"
                              placeholder="Optional notes"> --}}

                    <textarea id="notes" type="text" name="notes" maxlength="1000" value="{{ old('notes') }}"
                        placeholder="Optional notes"></textarea>

                    @error('notes')
                        <div class="field__error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn--filled">
                <span class="material-symbols-outlined">save</span>
                Save target
            </button>
        </form>
    </div>

    {{-- Student Details-style filters --}}
    @include('partials.data-filters', [
        'partners' => $partners,
        'branches' => $branches,
        'intakes' => $intakes,
        'courses' => $courses,
        'clearUrl' => route('targets.index'),
    ])

    @if ($comparison->isEmpty())
        <div class="card empty">
            <span class="material-symbols-outlined">track_changes</span>
            <h3>No targets found</h3>

            @if (request()->hasAny(['search', 'partner', 'branch', 'intake', 'course']))
                <p>No target matches the selected filters.</p>

                <p style="margin-top:16px">
                    <a href="{{ route('targets.index') }}" class="btn btn--tonal">
                        <span class="material-symbols-outlined">filter_alt_off</span>
                        Clear filters
                    </a>
                </p>
            @else
                <p>Add a partner target above to see the enrolment comparison.</p>
            @endif
        </div>
    @else
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="col-sticky">Partner</th>
                            <th>Intake</th>
                            <th>Branch</th>
                            <th class="is-num">Target</th>
                            <th class="is-num">Actual</th>
                            <th class="is-num">Variance</th>
                            <th style="width:200px">Achievement</th>
                            <th class="is-num">Target $</th>
                            <th class="is-num">Actual $</th>
                            <th class="is-num">Variance $</th>
                            <th style="width:70px"></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($comparison as $row)
                            @php
                                $percentage = $row['enrolment_pct'];

                                $progressClass =
                                    $percentage === null
                                        ? ''
                                        : ($percentage >= 100
                                            ? 'progress__fill--green'
                                            : ($percentage >= 70
                                                ? ''
                                                : ($percentage >= 40
                                                    ? 'progress__fill--yellow'
                                                    : 'progress__fill--red')));
                            @endphp

                            <tr>
                                <td class="col-sticky">
                                    <span class="cell-truncate" title="{{ $row['partner_name'] }}">
                                        {{ $row['partner_name'] }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge badge--blue">{{ $row['intake_code'] }}</span>
                                </td>

                                <td>{{ filled($row['branch']) ? $row['branch'] : 'All' }}</td>

                                <td class="is-num">
                                    {{ number_format($row['target_enrolment']) }}
                                </td>

                                <td class="is-num">
                                    <strong>{{ number_format($row['actual_enrolment']) }}</strong>
                                </td>

                                <td class="is-num"
                                    style="color:{{ $row['enrolment_variance'] >= 0 ? 'var(--g-green)' : 'var(--g-red)' }}">
                                    {{ $row['enrolment_variance'] > 0 ? '+' : '' }}
                                    {{ number_format($row['enrolment_variance']) }}
                                </td>

                                <td>
                                    <div style="display:flex; align-items:center; gap:8px">
                                        <div class="progress">
                                            <div class="progress__fill {{ $progressClass }}"
                                                style="width:{{ min((float) ($percentage ?? 0), 100) }}%"></div>
                                        </div>

                                        <span
                                            style="font-size:12px; color:var(--g-grey-700); min-width:52px; text-align:right">
                                            {{ $percentage !== null ? number_format($percentage, 1) . '%' : '–' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="is-money">
                                    {{ $row['target_commission'] > 0 ? '$' . number_format($row['target_commission'], 0) : '–' }}
                                </td>

                                <td class="is-money">
                                    ${{ number_format($row['actual_commission'], 0) }}
                                </td>

                                <td class="is-money"
                                    style="color:{{ $row['commission_variance'] >= 0 ? 'var(--g-green)' : 'var(--g-red)' }}">
                                    {{ $row['commission_variance'] > 0 ? '+' : '' }}
                                    ${{ number_format($row['commission_variance'], 0) }}
                                </td>

                                <td class="row-actions">
                                    <button type="button" class="btn btn--sm btn--text btn--danger"
                                        title="Delete target"
                                        data-target-delete="{{ route('targets.destroy', $row['target']) }}"
                                        data-target-name="{{ $row['partner_name'] }} / {{ $row['intake_code'] }}">
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

    {{-- Kept outside the table to avoid nested forms --}}
    <form method="POST" id="targetDeleteForm" style="display:none">
        @csrf
        @method('DELETE')
    </form>



    <style>
        .field textarea {
            height: auto;
            padding: 10px 12px;
            resize: vertical;
            min-height: 72px;
            width: 400px;
        }

        /* Target form layout */
        .target-form-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(180px, 1fr));
            gap: 18px 20px;
            align-items: start;
            margin-bottom: 18px;
        }

        .target-form-grid .field {
            min-width: 0;
        }

        .target-form-grid .field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .target-form-grid .field input,
        .target-form-grid .field select {
            width: 100%;
            min-height: 48px;
            box-sizing: border-box;
            padding: 10px 14px;
            border: 1px solid var(--g-grey-300, #d7dce2);
            border-radius: 8px;
            background: #fff;
            color: var(--g-grey-900, #202124);
            font: inherit;
            outline: none;
        }

        .target-form-grid .field input:focus,
        .target-form-grid .field select:focus {
            border-color: var(--g-blue, #1a73e8);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, .12);
        }

        .target-form-grid .field__hint {
            margin-top: 6px;
            color: var(--g-grey-600, #667085);
            font-size: 12px;
            line-height: 1.4;
        }

        .target-form-grid .field__error {
            margin-top: 6px;
            color: var(--g-red, #d93025);
            font-size: 12px;
        }

        /* Select2 */
        .target-form-grid .select2-container {
            width: 100% !important;
            min-width: 0;
        }

        .target-form-grid .select2-container .select2-selection--single {
            height: 48px !important;
            border: 1px solid var(--g-grey-300, #d7dce2) !important;
            border-radius: 8px !important;
            background: #fff !important;
            display: flex !important;
            align-items: center !important;
            box-sizing: border-box;
        }

        .target-form-grid .select2-container .select2-selection--single .select2-selection__rendered {
            width: 100%;
            padding: 0 40px 0 14px !important;
            line-height: 46px !important;
            color: var(--g-grey-900, #202124) !important;
        }

        .target-form-grid .select2-container .select2-selection--single .select2-selection__arrow {
            height: 46px !important;
            right: 8px !important;
        }

        .target-form-grid .select2-container--focus .select2-selection--single,
        .target-form-grid .select2-container--open .select2-selection--single {
            border-color: var(--g-blue, #1a73e8) !important;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, .12) !important;
        }

        /* Tom Select */
        .target-form-grid .ts-wrapper {
            width: 100%;
            min-width: 0;
        }

        .target-form-grid .ts-control {
            min-height: 48px !important;
            padding: 10px 42px 10px 14px !important;
            border: 1px solid var(--g-grey-300, #d7dce2) !important;
            border-radius: 8px !important;
            background: #fff !important;
            box-shadow: none !important;
            color: var(--g-grey-900, #202124) !important;
            display: flex;
            align-items: center;
            box-sizing: border-box;
        }

        .target-form-grid .ts-wrapper.focus .ts-control,
        .target-form-grid .ts-wrapper.dropdown-active .ts-control {
            border-color: var(--g-blue, #1a73e8) !important;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, .12) !important;
        }

        .target-form-grid .ts-wrapper.single .ts-control::after {
            right: 14px;
        }

        /* Choices.js */
        .target-form-grid .choices {
            width: 100%;
            margin-bottom: 0;
        }

        .target-form-grid .choices__inner {
            min-height: 48px !important;
            padding: 7px 40px 7px 14px !important;
            border: 1px solid var(--g-grey-300, #d7dce2) !important;
            border-radius: 8px !important;
            background: #fff !important;
            box-sizing: border-box;
        }

        .target-form-grid .choices.is-focused .choices__inner,
        .target-form-grid .choices.is-open .choices__inner {
            border-color: var(--g-blue, #1a73e8) !important;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, .12) !important;
        }

        .select2-dropdown,
        .ts-dropdown,
        .choices__list--dropdown {
            border: 1px solid var(--g-grey-300, #d7dce2) !important;
            border-radius: 8px !important;
            background: #fff !important;
            overflow: hidden;
        }

        .select2-search__field,
        .ts-dropdown input,
        .choices__input--cloned {
            min-height: 42px;
            border: 1px solid var(--g-grey-300, #d7dce2) !important;
            border-radius: 6px !important;
            outline: none;
        }

        @media (max-width: 1350px) {
            .target-form-grid {
                grid-template-columns: repeat(3, minmax(180px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .target-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        document.querySelectorAll('[data-target-delete]').forEach(function(button) {
            button.addEventListener('click', function() {
                const targetName = button.dataset.targetName || 'this target';

                if (!confirm('Remove ' + targetName + '?')) {
                    return;
                }

                const form = document.getElementById('targetDeleteForm');
                form.action = button.dataset.targetDelete;
                form.submit();
            });
        });
    </script>
@endsection
