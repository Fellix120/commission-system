<form method="GET" class="chips data-filter-form">
  @if (!empty($keepArchived))<input type="hidden" name="archived" value="1">@endif

  <label class="chip {{ request('search') ? 'is-active' : '' }}">
    <span class="material-symbols-outlined">search</span>
    <input type="text" name="search" placeholder="Student name or ID" value="{{ request('search') }}" class="chip-input"
      onchange="this.form.submit()">
  </label>
  {{-- <label class="chip {{ request('search') ? 'is-active' : '' }}">
    <span class="material-symbols-outlined">search</span>

    <input type="text" name="search" placeholder="Student name or ID" value="{{ request('search') }}" class="chip-input"
      autocomplete="off" oninput="
              clearTimeout(this.searchTimer);
              this.searchTimer = setTimeout(() => {
                  this.form.submit();
              }, 600);
          ">
  </label> --}}

  @isset($partners)
    <label class="chip {{ request('partner') ? 'is-active' : '' }}"><span class="material-symbols-outlined">school</span>
      <select name="partner" class="searchable-select" data-placeholder="All partners" onchange="this.form.submit()">
        <option value="">All partners</option>@foreach($partners as $v)<option value="{{ $v }}"
        @selected(request('partner') === $v)>{{ $v }}</option>@endforeach
      </select>
    </label>
  @endisset
  @isset($branches)
    <label class="chip {{ request('branch') ? 'is-active' : '' }}"><span class="material-symbols-outlined">store</span>
      <select name="branch" class="searchable-select" data-placeholder="All branches" onchange="this.form.submit()">
        <option value="">All branches</option>@foreach($branches as $v)<option value="{{ $v }}"
        @selected(request('branch') === $v)>{{ $v }}</option>@endforeach
      </select>
    </label>
  @endisset
  @isset($intakes)
    <label class="chip {{ request('intake') ? 'is-active' : '' }}"><span class="material-symbols-outlined">event</span>
      <select name="intake" class="searchable-select" data-placeholder="All intakes" onchange="this.form.submit()">
        <option value="">All intakes</option>@foreach($intakes as $v)<option value="{{ $v }}"
        @selected(request('intake') === $v)>{{ $v }}</option>@endforeach
      </select>
    </label>
  @endisset
  @isset($courses)
    <label class="chip {{ request('course') ? 'is-active' : '' }}"><span
        class="material-symbols-outlined">menu_book</span>
      <select name="course" class="searchable-select" data-placeholder="All courses" onchange="this.form.submit()">
        <option value="">All courses</option>@foreach($courses as $v)<option value="{{ $v }}"
        @selected(request('course') === $v)>{{ $v }}</option>@endforeach
      </select>
    </label>
  @endisset
  @isset($statuses)
    <label class="chip {{ request('status') ? 'is-active' : '' }}"><span
        class="material-symbols-outlined">filter_list</span>
      <select name="status" class="searchable-select" data-placeholder="All statuses" onchange="this.form.submit()">
        <option value="">All statuses</option>@foreach($statuses as $v)<option value="{{ $v }}"
        @selected(request('status') === $v)>{{ $v }}</option>@endforeach
      </select>
    </label>
  @endisset

  <button class="chip" type="submit"><span class="material-symbols-outlined">filter_alt</span> Filter</button>
  @if(request()->hasAny(['search', 'partner', 'branch', 'intake', 'course', 'status']))
    <a href="{{ $clearUrl }}" class="chip"><span class="material-symbols-outlined">close</span> Clear</a>
  @endif
</form>