<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'لوحة الإدارة') — {{ \App\Models\Setting::site('name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('brand/liqa-apple-touch-icon.png') }}">
    <meta name="theme-color" content="#0a0814">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="admin-body">
    @php
        $nav = [
            ['group' => 'الرئيسية', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'نظرة عامة', 'icon' => 'chart'],
            ['group' => 'الرئيسية', 'route' => 'admin.stats', 'match' => 'admin.stats', 'perm' => 'stats', 'label' => 'الإحصاءات', 'icon' => 'chart'],
            ['group' => 'الإدارة', 'route' => 'admin.users', 'match' => 'admin.users*', 'perm' => 'users', 'label' => 'المستخدمون', 'icon' => 'users'],
            ['group' => 'الإدارة', 'route' => 'admin.events', 'match' => 'admin.events*', 'perm' => 'events', 'label' => 'الأحداث', 'icon' => 'calendar'],
            ['group' => 'الإدارة', 'route' => 'admin.comments', 'match' => ['admin.comments*', 'admin.ratings*'], 'perm' => 'comments', 'label' => 'التعليقات والتقييمات', 'icon' => 'chat'],
            ['group' => 'التواصل', 'route' => 'admin.reports', 'match' => 'admin.reports*', 'perm' => 'reports', 'label' => 'البلاغات', 'icon' => 'chat', 'badge' => \App\Models\Report::where('status', 'open')->count()],
            ['group' => 'التواصل', 'route' => 'admin.messages', 'match' => 'admin.messages*', 'perm' => 'reports', 'label' => 'رسائل التواصل', 'icon' => 'send', 'badge' => \App\Models\ContactMessage::whereNull('read_at')->count()],
            ['group' => 'التواصل', 'route' => 'admin.broadcast', 'match' => 'admin.broadcast*', 'perm' => 'broadcast', 'label' => 'إشعار عام', 'icon' => 'send'],
            ['group' => 'النظام', 'route' => 'admin.logs', 'match' => 'admin.logs*', 'perm' => 'logs', 'label' => 'سجل الإجراءات', 'icon' => 'clock'],
            ['group' => 'النظام', 'route' => 'admin.settings', 'match' => 'admin.settings*', 'perm' => 'settings', 'label' => 'إعدادات المنصة', 'icon' => 'bolt'],
            ['group' => 'النظام', 'route' => 'admin.content', 'match' => ['admin.content*', 'admin.pages*'], 'perm' => 'settings', 'label' => 'إدارة المحتوى', 'icon' => 'edit'],
            ['group' => 'النظام', 'route' => 'admin.security', 'match' => 'admin.security*', 'label' => 'أمان الحساب', 'icon' => 'shield'],
        ];
        $nav = array_values(array_filter($nav, fn ($i) => ! isset($i['perm']) || auth()->user()->hasPermission($i['perm'])));
        $groups = collect($nav)->groupBy('group');
        $me = auth()->user();
    @endphp

    <div class="admin-backdrop" id="adminBackdrop"></div>

    <aside class="admin-sidebar" id="adminSidebar" aria-label="أقسام الإدارة">
        <div class="admin-sidebar__brand">
            <a class="d-flex align-items-center gap-2 text-decoration-none" href="{{ route('admin.dashboard') }}">
                <x-logo :size="34" />
                <span class="d-flex flex-column lh-sm">
                    <span class="fs-5 fw-bold text-white">{{ \App\Models\Setting::site('name') }}</span>
                    <span class="small text-secondary">لوحة الإدارة</span>
                </span>
            </a>
        </div>

        <nav class="admin-sidebar__nav">
            @foreach ($groups as $title => $items)
                <div class="admin-sidebar__group">{{ $title }}</div>
                @foreach ($items as $item)
                    <a href="{{ route($item['route']) }}" class="admin-link {{ request()->routeIs($item['match']) ? 'is-active' : '' }}">
                        <x-icon :name="$item['icon']" :size="18" /> <span>{{ $item['label'] }}</span>
                        @if (($item['badge'] ?? 0) > 0)<span class="count">{{ $item['badge'] }}</span>@endif
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="admin-sidebar__foot">
            <div class="d-flex align-items-center gap-2 mb-2 min-w-0">
                <x-avatar :user="$me" :size="36" />
                <span class="min-w-0">
                    <span class="d-block fw-bold small text-truncate">{{ $me->name }}</span>
                    <span class="d-block small text-secondary">{{ $me->isOwner() ? 'مالك المنصة' : 'مدير' }}</span>
                </span>
            </div>
            <a href="{{ route('landing') }}" class="admin-link" target="_blank" rel="noopener"><x-icon name="calendar" :size="18" /> <span>عرض الموقع</span></a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="admin-link admin-link--danger w-100 border-0 bg-transparent"><x-icon name="shield" :size="18" /> <span>تسجيل الخروج</span></button>
            </form>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="admin-burger" id="adminBurger" aria-label="القائمة" aria-controls="adminSidebar">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <div class="admin-crumb">
                <span class="text-secondary">لوحة الإدارة</span>
                <span class="text-secondary mx-1">/</span>
                <span class="fw-bold">@yield('title', 'نظرة عامة')</span>
            </div>
            <span class="ms-auto"></span>
            <span class="small text-secondary d-none d-md-inline">{{ $me->name }}</span>
        </header>

        <main class="admin-content">
            @include('partials.alerts')
            @yield('content')
        </main>
    </div>

    <script>
        (function () {
            var b = document.body, t = document.getElementById('adminBurger'), o = document.getElementById('adminBackdrop');
            function set(v) { b.classList.toggle('admin-nav-open', v); }
            t.addEventListener('click', function () { set(!b.classList.contains('admin-nav-open')); });
            o.addEventListener('click', function () { set(false); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
        })();
    </script>
    @stack('scripts')
</body>
</html>
