<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · StudioAI Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('panel-assets/admin.css') }}?v={{ filemtime(public_path('panel-assets/admin.css')) }}">
</head>
<body>
@php
    $admin = auth('admin')->user();
    $nav = [
        ['Genel', [
            ['admin.dashboard', [], 'space_dashboard', 'Dashboard', 'admin.dashboard'],
            ['admin.users.index', [], 'group', 'Kullanıcılar', 'admin.users.*'],
            ['admin.generations.index', [], 'auto_awesome', 'Üretimler', 'admin.generations.*'],
        ]],
        ['Katalog', collect(\App\Http\Controllers\Admin\CatalogController::menu())
            ->map(fn ($m, $key) => ['admin.catalog.index', ['resource' => $key], $m['icon'], $m['title'], null, $key])->values()->all()],
        ['Sistem', array_values(array_filter([
            ['admin.settings', [], 'tune', 'Ayarlar', 'admin.settings*'],
            ['admin.logs.credits', [], 'toll', 'Kredi Hareketleri', 'admin.logs.credits'],
            ['admin.logs.ai', [], 'monitoring', 'AI İstek Logları', 'admin.logs.ai'],
            ['admin.logs.failed', [], 'error', 'Başarısız İşler', 'admin.logs.failed*'],
            $admin->isSuperAdmin() ? ['admin.admins.index', [], 'shield_person', 'Yöneticiler', 'admin.admins.*'] : null,
        ]))],
    ];
@endphp
<div class="shell">
    <aside class="sidebar">
        <a href="{{ route('admin.dashboard') }}" class="brand">
            <span class="brand-mark"><span class="ms">auto_awesome</span></span>
            <span>
                <span class="brand-name" style="display:block;color:var(--on-surface)">StudioAI</span>
                <span class="brand-sub">Yönetim Paneli</span>
            </span>
        </a>

        @foreach ($nav as [$group, $links])
            <div class="nav-group">{{ $group }}</div>
            @foreach ($links as $link)
                @php
                    [$route, $params, $icon, $label, $pattern] = $link;
                    $active = $pattern ? request()->routeIs($pattern) : (request()->routeIs('admin.catalog.*') && request()->route('resource') === ($link[5] ?? null));
                @endphp
                <a href="{{ route($route, $params) }}" class="nav-link {{ $active ? 'active' : '' }}">
                    <span class="ms {{ $active ? 'fill' : '' }}">{{ $icon }}</span>{{ $label }}
                </a>
            @endforeach
        @endforeach

        <div class="sidebar-foot">
            <div class="admin-card">
                <span class="avatar"><span class="ms" style="font-size:18px">person</span></span>
                <div style="min-width:0;flex:1">
                    <div class="truncate" style="font-weight:600">{{ $admin->name }}</div>
                    <div class="label-sm variant">{{ $admin->role->label() }}</div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="btn btn-ghost btn-icon" title="Çıkış yap"><span class="ms">logout</span></button>
                </form>
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="row">
                <button class="btn btn-ghost btn-icon menu-toggle" data-menu-toggle><span class="ms">menu</span></button>
                <div>
                    <h1 class="headline-md">@yield('title')</h1>
                    @hasSection('subtitle')<div class="label-sm variant">@yield('subtitle')</div>@endif
                </div>
            </div>
            <div class="topbar-actions">@yield('actions')</div>
        </header>

        <div class="content stack">
            @if (session('success'))
                <div class="flash success" data-autohide>
                    <span class="icon"><span class="ms">check</span></span>
                    <div>{{ session('success') }}</div>
                </div>
            @endif
            @if (session('error'))
                <div class="flash error">
                    <span class="icon"><span class="ms">priority_high</span></span>
                    <div>{{ session('error') }}</div>
                </div>
            @endif
            @if ($errors->any())
                <div class="flash error">
                    <span class="icon"><span class="ms">priority_high</span></span>
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>
<script src="{{ asset('panel-assets/admin.js') }}?v={{ filemtime(public_path('panel-assets/admin.js')) }}"></script>
</body>
</html>
