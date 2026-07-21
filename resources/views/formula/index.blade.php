@extends('layouts.app')
@section('title', 'Student Details-Formula - Commission Dashboard')

@section('content')
    @php $archived = request()->boolean('archived'); @endphp

    <div class="page-head">
        <h1>Student Details-Formula</h1>
        <span class="badge badge--grey">{{ $rows->total() }} rows</span>
        <span class="card__sub">Every column resolved through the formula, with the CRM rows behind it</span>
        <div class="page-head__actions">
            <a href="{{ route('students.index') }}" class="btn btn--tonal"><span
                    class="material-symbols-outlined">table_rows</span> Student Details</a>
            <a href="{{ route('export.formula', request()->query()) }}" class="btn btn--outlined"><span
                    class="material-symbols-outlined">download</span> Export</a>
            {{-- <form method="POST" action="{{ route('system.fresh') }}"
                data-confirm="This runs migrate:fresh --seed and permanently clears ALL tables. Continue?">@csrf<button
                    class="btn btn--outlined btn--danger"><span class="material-symbols-outlined">delete_forever</span>
                    Clear all tables</button></form> --}}
        </div>
    </div>

    {{-- <div class="card card--pad key-card" style="margin-bottom:20px">
  <div class="key-label">The key</div>
  <code class="formula formula-key">{{ $keyFormula }}</code>
  <p class="key-note">
    Column G spills the unique Client IDs out of CRM Data, and every other column looks up
    against it. Here that is <code>refreshAll()</code> &mdash; one row per distinct Client ID
    with a Completed status.
  </p>
</div> --}}

    {{-- Filters mirror the Student Details controls, so the two screens line up. --}}
    @include('partials.data-filters', [
        'partners' => $partners,
        'branches' => $branches,
        'intakes' => $intakes,
        'courses' => $courses,
        'keepArchived' => $archived,
        'clearUrl' => route('formula.index', ['archived' => $archived ? 1 : null]),
    ])

    @if ($rows->isEmpty())
        <div class="card empty">
            <span class="material-symbols-outlined">function</span>
            <h3>Nothing to resolve</h3>
            <p>No students match these filters.</p>
        </div>
    @else
        {{-- The list: every student, every formula-derived column, source row count. --}}
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="col-sticky">H &middot; Name</th>
                            <th>G &middot; Client ID</th>
                            <th class="is-num">CRM rows</th>
                            <th>A &middot; Branch</th>
                            <th>F &middot; Partner</th>
                            <th>B &middot; Applied Intake</th>
                            <th>I &middot; Product Name</th>
                            <th>J &middot; Start</th>
                            <th>K &middot; End</th>
                            <th class="is-num">M &middot; Duration</th>
                            <th class="is-num">L &middot; Fee Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            @php
                                $n = (int) ($rowCounts[$r->client_id_crm] ?? 0);
                                $short = $r->feeLooksUnderstated($n);
                            @endphp
                            <tr class="{{ $r->is_archived ? 'is-archived' : '' }}">
                                <td class="col-sticky">
                                    <a href="{{ route('formula.index', array_merge(request()->query(), ['client_id' => $r->client_id_crm])) }}"
                                        style="color:var(--g-blue); text-decoration:none; font-weight:500">
                                        {{ $r->name }}
                                    </a>
                                </td>
                                <td><code class="mono">{{ $r->client_id_crm }}</code></td>
                                <td class="is-num">
                                    @if ($n === 0)
                                        <span class="badge badge--red" title="No CRM rows back this row">0</span>
                                    @else
                                        {{ $n }}
                                    @endif
                                </td>
                                <td>{{ $r->branch }}</td>
                                <td><span class="cell-truncate">{{ $r->partner_name }}</span></td>
                                <td>{{ $r->applied_intake_date }}</td>
                                <td><span class="cell-truncate"
                                        title="{{ $r->product_name }}">{{ $r->product_name }}</span></td>
                                <td>{{ $r->start_date?->format('d/m/Y') }}</td>
                                <td>{{ $r->end_date?->format('d/m/Y') }}</td>
                                <td class="is-num">{{ number_format((float) $r->course_duration, 4) }}</td>
                                <td class="is-money">
                                    {{ number_format((float) $r->fee_total, 0) }}
                                    @if ($short)
                                        <span class="badge badge--yellow"
                                            title="Product Name lists more courses than there are CRM rows – the export is missing rows">short</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('formula.index', array_merge(request()->query(), ['client_id' => $r->client_id_crm])) }}"
                                        class="btn btn--sm btn--text" title="Trace this student">
                                        <span class="material-symbols-outlined">function</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pager">
            <span>Showing {{ $rows->firstItem() }}&ndash;{{ $rows->lastItem() }} of {{ $rows->total() }}</span>
            {{ $rows->links() }}
        </div>
    @endif

    {{-- Drill-down for one student. --}}
    @if ($student)
        <div class="card card--pad" style="margin:20px 0">
            <div style="margin-bottom:16px; display:flex; align-items:center; gap:10px">
                <div>
                    <div class="card__title">Trace &middot; {{ $student->name }}</div>
                    <div class="card__sub">Each formula resolved against this student's real CRM rows</div>
                </div>
                <a href="{{ route('formula.index',collect(request()->query())->except('client_id')->all()) }}"
                    class="btn btn--text" style="margin-left:auto">
                    <span class="material-symbols-outlined">close</span> Close
                </a>
            </div>

            <div class="trace-grid">
                @foreach ($trace as $t)
                    <div class="trace-row">
                        <div class="trace-field">{{ $t['field'] }}</div>
                        <code class="formula">{{ $t['excel'] }}</code>
                        <div class="trace-resolved">{{ $t['resolved'] }}</div>
                        <div class="trace-value">
                            {{ $t['value'] }}
                            @if ($t['flag'] === 'warning')
                                <span class="badge badge--yellow">understated</span>
                            @elseif ($t['flag'] === 'danger')
                                <span class="badge badge--red">open</span>
                            @elseif ($t['flag'] === 'success')
                                <span class="badge badge--green">settled</span>
                            @elseif ($t['flag'] === 'manual')
                                <span class="badge badge--green">yours</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="crm-behind">
                <h3>The {{ $crmRows->count() }} CRM row{{ $crmRows->count() === 1 ? '' : 's' }} behind those figures</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Start</th>
                                <th>End</th>
                                <th class="is-num">Fee Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($crmRows as $row)
                                <tr>
                                    <td>{{ $row->product_name }}</td>
                                    <td>{{ $row->start_date?->format('d/m/Y') }}</td>
                                    <td>{{ $row->end_date?->format('d/m/Y') }}</td>
                                    <td class="is-money">{{ number_format((float) $row->fee_total, 2) }}</td>
                                    <td><span class="badge badge--green">{{ $row->status }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="color:var(--g-grey-500)">No CRM rows for this Client ID.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Reference tables. --}}
    {{-- <div class="card card--pad" style="margin-bottom:20px">
  <div style="margin-bottom:16px">
    <div class="card__title">Columns on the formula sheet</div>
    <div class="card__sub">Quoted from the workbook, with the PHP that runs instead</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th style="width:48px">Col</th><th>Field</th><th>Excel formula</th><th>PHP</th><th>Method</th></tr>
      </thead>
      <tbody>
        @foreach ($columns as $c)
          <tr class="{{ $c['kind'] === 'key' ? 'row-key' : '' }}">
            <td><code class="mono">{{ $c['column'] }}</code></td>
            <td>
              <div style="font-weight:500">{{ $c['field'] }}</div>
              @if ($c['note'])<div class="fnote">{{ $c['note'] }}</div>@endif
            </td>
            <td><code class="formula">{{ $c['formula'] }}</code></td>
            <td><code class="php">{{ $c['php'] }}</code></td>
            <td><code class="php">{{ $c['method'] }}</code></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div> --}}

    {{-- <div class="card card--pad">
  <div style="margin-bottom:16px">
    <div class="card__title">Columns not on the formula sheet</div>
    <div class="card__sub">Derived, typed by an operator, or matched in from Invoice</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th style="width:48px">Col</th><th>Field</th><th>Rule</th><th>PHP</th><th>Method</th></tr>
      </thead>
      <tbody>
        @foreach ($derived as $c)
          <tr>
            <td><code class="mono">{{ $c['column'] }}</code></td>
            <td>
              <div style="font-weight:500">
                {{ $c['field'] }}
                @if ($c['kind'] === 'manual')
                  <span class="badge badge--green">yours</span>
                @elseif ($c['kind'] === 'invoice')
                  <span class="badge badge--grey">Invoice</span>
                @endif
              </div>
              @if ($c['note'])<div class="fnote">{{ $c['note'] }}</div>@endif
            </td>
            <td><code class="formula">{{ $c['formula'] }}</code></td>
            <td><code class="php">{{ $c['php'] }}</code></td>
            <td><code class="php">{{ $c['method'] }}</code></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div> --}}

    <style>
        .key-card {
            border-left: 4px solid var(--g-blue)
        }

        .key-label {
            font-size: 11px;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: var(--g-grey-500);
            margin-bottom: 8px
        }

        .key-note {
            font-size: 13px;
            color: var(--g-grey-700);
            margin: 12px 0 0;
            line-height: 1.6
        }

        .key-note code {
            font-family: 'Roboto Mono', monospace;
            font-size: 12px;
            background: var(--g-grey-50);
            padding: 1px 5px;
            border-radius: 4px
        }

        .formula {
            display: inline-block;
            font-family: 'Roboto Mono', monospace;
            font-size: 12px;
            background: var(--g-grey-50);
            border: 1px solid var(--g-grey-300);
            border-radius: 4px;
            padding: 4px 8px;
            color: var(--g-grey-900);
            white-space: pre-wrap;
            word-break: break-word
        }

        .formula-key {
            font-size: 14px;
            background: var(--g-blue-light);
            border-color: #c6dafc;
            color: var(--g-blue-dark)
        }

        .php {
            font-family: 'Roboto Mono', monospace;
            font-size: 12px;
            color: var(--g-green);
            word-break: break-word
        }

        .mono {
            font-family: 'Roboto Mono', monospace;
            font-size: 12px;
            color: var(--g-grey-700)
        }

        .row-key {
            background: #f8fbff
        }

        .fnote {
            font-size: 12px;
            color: var(--g-grey-700);
            margin-top: 3px;
            line-height: 1.5
        }

        .trace-grid {
            border: 1px solid var(--g-grey-300);
            border-radius: 8px;
            overflow: hidden
        }

        .trace-row {
            display: grid;
            grid-template-columns: 150px 1fr 170px 170px;
            gap: 14px;
            align-items: center;
            padding: 10px 14px;
            border-bottom: 1px solid var(--g-grey-300)
        }

        .trace-row:last-child {
            border-bottom: 0
        }

        .trace-row:nth-child(odd) {
            background: var(--g-grey-50)
        }

        .trace-field {
            font-weight: 500;
            font-size: 13px
        }

        .trace-resolved {
            font-size: 12px;
            color: var(--g-grey-700)
        }

        .trace-value {
            font-family: 'Roboto Mono', monospace;
            font-size: 13px;
            text-align: right
        }

        .crm-behind {
            margin-top: 22px
        }

        .crm-behind h3 {
            font-size: 14px;
            font-weight: 500;
            margin: 0 0 10px;
            color: var(--g-grey-700)
        }

        @media (max-width:900px) {
            .trace-row {
                grid-template-columns: 1fr;
                gap: 6px
            }

            .trace-value {
                text-align: left
            }
        }
    </style>
@endsection
