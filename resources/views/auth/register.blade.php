@extends('layouts.guest')
@section('title', 'Register - Commission Dashboard')
@section('content')
<h1>Create account</h1>
<p class="subtitle">Register to start using the commission dashboard.</p>
<form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="field"><label for="name">Full name</label><input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">@error('name')<div class="error">{{ $message }}</div>@enderror</div>
    <div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">@error('email')<div class="error">{{ $message }}</div>@enderror</div>
    <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" required autocomplete="new-password">@error('password')<div class="error">{{ $message }}</div>@enderror</div>
    <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
    <button class="btn" type="submit">Create account</button>
</form>
<div class="auth-footer">Already registered? <a href="{{ route('login') }}">Login</a></div>
@endsection
