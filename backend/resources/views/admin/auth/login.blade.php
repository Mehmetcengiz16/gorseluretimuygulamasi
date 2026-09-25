<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giriş · StudioAI Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('panel-assets/admin.css') }}?v={{ filemtime(public_path('panel-assets/admin.css')) }}">
</head>
<body>
<div class="auth-page">
    <form method="POST" action="{{ route('admin.login') }}" class="auth-card">
        @csrf
        <div class="auth-logo"><span class="ms">auto_awesome</span></div>
        <div>
            <div class="headline-lg">StudioAI</div>
            <div class="muted">Yönetim paneline giriş yap</div>
        </div>

        <div class="field">
            <label for="email">E-posta</label>
            <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="admin@studioai.test">
            @error('email')<div class="error-msg">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="password">Şifre</label>
            <input class="input" id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
        </div>

        <label class="switch">
            <input type="checkbox" name="remember" value="1">
            <span class="track"></span>
            Beni hatırla
        </label>

        <button class="btn btn-primary btn-block" type="submit">
            Giriş Yap <span class="ms">arrow_forward</span>
        </button>
    </form>
</div>
</body>
</html>
