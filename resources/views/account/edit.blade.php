@extends('layouts.app')
@section('title', 'My Account - Commission Dashboard')
@section('content')
<div class="page-head">
    <div>
        <h1>My Account</h1>
        <span class="card__sub">Manage your profile information and password.</span>
    </div>
</div>

<div class="account-grid">
    <section class="card account-card">
        <div class="card__head">
            <span class="material-symbols-outlined" style="color:var(--g-blue)">person</span>
            <div><div class="card__title">Profile information</div><div class="card__sub">Update your name and email address.</div></div>
        </div>
        <form method="POST" action="{{ route('account.profile.update') }}" class="account-form">
            @csrf @method('PUT')
            <div class="form-field"><label for="name">Full name</label><input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>@error('name')<div class="field-error">{{ $message }}</div>@enderror</div>
            <div class="form-field"><label for="email">Email address</label><input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>@error('email')<div class="field-error">{{ $message }}</div>@enderror</div>
            <button class="btn btn--filled" type="submit"><span class="material-symbols-outlined">save</span> Save profile</button>
        </form>
    </section>

    <section class="card account-card">
        <div class="card__head">
            <span class="material-symbols-outlined" style="color:var(--g-green)">lock</span>
            <div><div class="card__title">Change password</div><div class="card__sub">Use at least 8 characters with letters and numbers.</div></div>
        </div>
        <form method="POST" action="{{ route('account.password.update') }}" class="account-form">
            @csrf @method('PUT')
            <div class="form-field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" required>@error('current_password')<div class="field-error">{{ $message }}</div>@enderror</div>
            <div class="form-field"><label for="password">New password</label><input id="password" name="password" type="password" required>@error('password')<div class="field-error">{{ $message }}</div>@enderror</div>
            <div class="form-field"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" required></div>
            <button class="btn btn--filled" type="submit"><span class="material-symbols-outlined">key</span> Update password</button>
        </form>
    </section>
</div>
@endsection
