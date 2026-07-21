<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Commission Dashboard')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@400;500;700&family=Material+Symbols+Outlined&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box} body{margin:0;min-height:100vh;font-family:'Google Sans','Roboto',sans-serif;background:linear-gradient(135deg,#f8f9fa 0%,#e8f0fe 100%);color:#202124;display:grid;place-items:center;padding:24px}.auth-shell{width:100%;max-width:460px}.auth-brand{display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:22px}.auth-logo{width:46px;height:46px;border-radius:14px;background:#1a73e8;color:#fff;display:grid;place-items:center;font-weight:700;font-size:18px;box-shadow:0 8px 24px rgba(26,115,232,.25)}.auth-brand strong{font-size:22px}.auth-card{background:#fff;border:1px solid #dadce0;border-radius:22px;padding:34px;box-shadow:0 12px 40px rgba(60,64,67,.15)}h1{margin:0 0 8px;font-size:28px}.subtitle{color:#5f6368;margin:0 0 26px}.field{margin-bottom:18px}.field label{display:block;font-size:13px;font-weight:500;margin-bottom:7px}.field input{width:100%;height:46px;border:1px solid #dadce0;border-radius:10px;padding:0 13px;font:inherit;outline:none}.field input:focus{border-color:#1a73e8;box-shadow:0 0 0 3px rgba(26,115,232,.12)}.error{color:#d93025;font-size:12px;margin-top:5px}.alert{padding:11px 13px;border-radius:9px;margin-bottom:18px;background:#e6f4ea;color:#137333}.remember{display:flex;align-items:center;gap:8px;color:#5f6368;margin-bottom:20px}.btn{width:100%;height:46px;border:0;border-radius:10px;background:#1a73e8;color:#fff;font:inherit;font-weight:600;cursor:pointer}.btn:hover{background:#1967d2}.auth-footer{text-align:center;margin-top:20px;color:#5f6368}.auth-footer a{color:#1a73e8;text-decoration:none;font-weight:500}.material-symbols-outlined{font-family:'Material Symbols Outlined'}@media(max-width:520px){.auth-card{padding:25px 20px;border-radius:16px}}
    </style>
</head>
<body>
<div class="auth-shell">
    <div class="auth-brand"><div class="auth-logo">CD</div><strong>Commission Dashboard</strong></div>
    <div class="auth-card">@yield('content')</div>
</div>
</body>
</html>
