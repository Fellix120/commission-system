@extends('layouts.app')
@section('title', $student->name.' - Commission Dashboard')

@section('content')
<div class="page-head">
  <a href="{{ route('students.index') }}" class="btn btn--text">
    <span class="material-symbols-outlined">arrow_back</span>
  </a>
  <h1>{{ $student->name }}</h1>
  @if ($student->is_archived)
    <span class="badge badge--grey">Archived {{ $student->archived_at?->format('d/m/Y') }}</span>
  @elseif ($student->isSettled())
    <span class="badge badge--green">Settled</span>
  @else
    <span class="badge badge--yellow">Outstanding</span>
  @endif

  <div class="page-head__actions">
    @if ($student->is_archived)
      <form method="POST" action="{{ route('students.unarchive', $student) }}">
        @csrf
        <button class="btn btn--outlined"><span class="material-symbols-outlined">unarchive</span> Restore</button>
      </form>
    @else
      <form method="POST" action="{{ route('students.archive', $student) }}"
            onsubmit="return confirm('Archive this student? Their value-pasted figures are frozen as they stand.')">
        @csrf
        @unless ($student->isSettled())<input type="hidden" name="force" value="1">@endunless
        <button class="btn btn--outlined"><span class="material-symbols-outlined">archive</span> Archive</button>
      </form>
    @endif
  </div>
</div>

<div class="detail-grid" style="margin-bottom:20px">
  <div class="card card--pad">
    <div class="card__title" style="margin-bottom:8px">Identity</div>
    <div class="kv"><div class="kv__k">Branch</div><div class="kv__v">{{ $student->branch ?? '–' }}</div></div>
    <div class="kv"><div class="kv__k">Applied Intake Date</div><div class="kv__v">{{ $student->applied_intake_date ?? '–' }}</div></div>
    <div class="kv"><div class="kv__k">Contact ID</div><div class="kv__v">{{ $student->contact_id ?? '–' }}</div></div>
    <div class="kv"><div class="kv__k">Client ID (CRM / Invoice)</div>
      <div class="kv__v">{{ $student->client_id_crm }} / {{ $student->client_id_invoice }}</div></div>
    <div class="kv"><div class="kv__k">Partner</div><div class="kv__v">{{ $student->partner_name ?? '–' }}</div></div>
    <div class="kv"><div class="kv__k">Sub / Super agent</div>
      <div class="kv__v">{{ $student->sub_agent_name ?? '–' }} / {{ $student->super_agent_name ?? '–' }}</div></div>
  </div>

  <div class="card card--pad">
    <div class="card__title" style="margin-bottom:8px">Course</div>
    <div class="kv"><div class="kv__k">Product Name</div><div class="kv__v">{{ $student->product_name ?? '–' }}</div></div>
    <div class="kv"><div class="kv__k">Start (MINIFS)</div><div class="kv__v">{{ $student->start_date?->format('d/m/Y') ?? '–' }}</div></div>
    <div class="kv"><div class="kv__k">End (MAXIFS)</div><div class="kv__v">{{ $student->end_date?->format('d/m/Y') ?? '–' }}</div></div>
    <div class="kv"><div class="kv__k">Course duration</div>
      <div class="kv__v">{{ number_format((float) $student->course_duration, 4) }} years</div></div>
    <div class="kv"><div class="kv__k">Fee Total (SUMIFS)</div>
      <div class="kv__v">${{ number_format((float) $student->fee_total, 2) }}</div></div>
  </div>

  <div class="card card--pad">
    <div class="card__title" style="margin-bottom:8px">Commission position</div>
    <div class="kv"><div class="kv__k">Credit Fee (CR fee)</div>
      <div class="kv__v">${{ number_format((float) $student->credit_fee, 2) }}</div></div>
    <div class="kv"><div class="kv__k">Paid Fee</div><div class="kv__v">${{ number_format((float) $student->paid_fee, 2) }}</div></div>
    <div class="kv"><div class="kv__k">Remaining Fee</div>
      <div class="kv__v" style="color:{{ (float) $student->remaining_fee > 0 ? 'var(--g-red)' : 'var(--g-green)' }}">
        ${{ number_format((float) $student->remaining_fee, 2) }}
      </div></div>
    <div class="kv"><div class="kv__k">Bonus due / paid</div>
      <div class="kv__v">${{ number_format((float) $student->bonus_due, 2) }} / ${{ number_format((float) $student->paid_bonus, 2) }}</div></div>
    <div class="kv"><div class="kv__k">Values refreshed</div>
      <div class="kv__v">{{ $student->values_refreshed_at?->format('d/m/Y H:i') ?? 'never' }}</div></div>
  </div>
</div>

{{-- Requirement 2 --}}
<div class="card" style="margin-bottom:20px">
  <div class="card__head">
    <span class="material-symbols-outlined" style="color:var(--g-yellow)">payments</span>
    <div>
      <div class="card__title">Intake-wise commission received</div>
      <div class="card__sub">Grouped from Invoice &ldquo;Commission intake&rdquo;</div>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Intake</th><th class="is-num">Amount received</th></tr></thead>
      <tbody>
        @forelse ($student->intakeCommissions->sortBy('intake_code') as $ic)
          <tr>
            <td><span class="badge badge--blue">{{ $ic->intake_code }}</span></td>
            <td class="is-money">${{ number_format((float) $ic->amount_received, 2) }}</td>
          </tr>
        @empty
          <tr><td colspan="2" style="color:var(--g-grey-500)">No commission received yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Editable operator fields --}}
<div class="card card--pad" style="margin-bottom:20px">
  <div class="card__title" style="margin-bottom:16px">Operator fields</div>
  <form method="POST" action="{{ route('students.update', $student) }}">
    @csrf @method('PUT')
    <div class="form-grid">
      <div class="field">
        <label>Credit Fee (CR fee)</label>
        <input type="number" step="0.01" name="credit_fee" value="{{ old('credit_fee', $student->credit_fee) }}">
        <div class="field__hint">Fee the partner pays commission on. Survives every refresh.</div>
      </div>
      <div class="field">
        <label>Bonus Due</label>
        <input type="number" step="0.01" name="bonus_due" value="{{ old('bonus_due', $student->bonus_due) }}">
      </div>
      <div class="field">
        <label>Fee Adjustment</label>
        <input type="number" step="0.01" name="fee_adjustment" value="{{ old('fee_adjustment', $student->fee_adjustment) }}">
      </div>
      <div class="field">
        <label>Bonus Adjustment</label>
        <input type="number" step="0.01" name="bonus_adjustment" value="{{ old('bonus_adjustment', $student->bonus_adjustment) }}">
      </div>
    </div>
    <div class="field">
      <label>HO Remarks if Any</label>
      <textarea name="ho_remarks">{{ old('ho_remarks', $student->ho_remarks) }}</textarea>
    </div>
    <div class="field">
      <label>Branch Remarks</label>
      <textarea name="branch_remarks">{{ old('branch_remarks', $student->branch_remarks) }}</textarea>
    </div>
    <button class="btn btn--filled"><span class="material-symbols-outlined">save</span> Save</button>
  </form>
</div>

{{-- Traceability: the rows behind the numbers --}}
<div class="card" style="margin-bottom:20px">
  <div class="card__head">
    <span class="material-symbols-outlined" style="color:var(--g-blue)">receipt_long</span>
    <div class="card__title">Invoice rows ({{ $student->invoices->count() }})</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>App ID</th><th>Course</th><th>Intake</th><th class="is-num">Total fee</th>
        <th class="is-num">Rate</th><th class="is-num">Commission</th><th class="is-num">Net</th>
        <th class="is-num">Bonus</th><th>PO</th>
      </tr></thead>
      <tbody>
        @forelse ($student->invoices as $inv)
          <tr>
            <td>{{ $inv->application_id }}</td>
            <td><span class="cell-truncate">{{ $inv->course }}</span></td>
            <td><span class="badge badge--blue">{{ $inv->commission_intake }}</span></td>
            <td class="is-money">{{ number_format((float) $inv->total_fee, 2) }}</td>
            <td class="is-num">{{ number_format((float) $inv->rate * 100, 2) }}%</td>
            <td class="is-money">{{ number_format((float) $inv->commission, 2) }}</td>
            <td class="is-money">{{ number_format((float) $inv->net_commission, 2) }}</td>
            <td class="is-money">{{ number_format((float) $inv->bonus, 2) }}</td>
            <td>{{ $inv->po_number }}</td>
          </tr>
        @empty
          <tr><td colspan="9" style="color:var(--g-grey-500)">No invoice rows.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card__head">
    <span class="material-symbols-outlined" style="color:var(--g-green)">table_chart</span>
    <div class="card__title">CRM Data rows ({{ $student->crmRows->count() }})</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Application ID</th><th>Product</th><th>Start</th><th>End</th>
        <th class="is-num">Fee Total</th><th>Status</th><th>Stage</th>
      </tr></thead>
      <tbody>
        @foreach ($student->crmRows as $row)
          <tr>
            <td>{{ $row->application_id }}</td>
            <td><span class="cell-truncate">{{ $row->product_name }}</span></td>
            <td>{{ $row->start_date?->format('d/m/Y') }}</td>
            <td>{{ $row->end_date?->format('d/m/Y') }}</td>
            <td class="is-money">{{ number_format((float) $row->fee_total, 2) }}</td>
            <td>
              <span class="badge {{ $row->status === 'Completed' ? 'badge--green' : 'badge--grey' }}">
                {{ $row->status }}
              </span>
            </td>
            <td>{{ $row->current_stage }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
