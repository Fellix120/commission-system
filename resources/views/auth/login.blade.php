@extends('layouts.guest')
@section('title', 'Login - Commission Dashboard')
@section('content')
<h1>Welcome back</h1>
<p class="subtitle">Sign in to access your commission dashboard.</p>
@if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">@error('email')<div class="error">{{ $message }}</div>@enderror</div>
    <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" required autocomplete="current-password">@error('password')<div class="error">{{ $message }}</div>@enderror</div>
    <label class="remember"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
    <button class="btn" type="submit">Login</button>
</form>
<div class="auth-footer">No account yet? <a href="{{ route('register') }}">Create an account</a></div>
@endsection
