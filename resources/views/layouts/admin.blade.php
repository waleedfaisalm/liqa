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
<body>
    @php
        $nav = [
            ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'نظرة عامة', 'icon' => 'chart'],
            ['route' => 'admin.users', 'match' => 'admin.users*', 'perm' => 'users', 'label' => 'المستخدمون', 'icon' => 'users'],
            ['route' => 'admin.events', 'match' => 'admin.events*', 'perm' => 'events', 'label' => 'الأحداث', 'icon' => 'calendar'],
            ['route' => 'admin.comments', 'match' => ['admin.comments*', 'admin.ratings*'], 'perm' => 'comments', 'label' => 'التعليقات والتقييمات', 'icon' => 'chat'],
            ['route' => 'admin.broadcast', 'match' => 'admin.broadcast*', 'perm' => 'broadcast', 'label' => 'إشعار عام', 'icon' => 'send'],
            ['route' => 'admin.logs', 'match' => 'admin.logs*', 'perm' => 'logs', 'label' => 'سجل الإجراءات', 'icon' => 'clock'],
            ['route' => 'admin.reports', 'match' => 'admin.reports*', 'perm' => 'reports', 'label' => 'البلاغات', 'icon' => 'chat', 'badge' => \App\Models\Report::where('status', 'open')->count()],
            ['route' => 'admin.messages', 'match' => 'admin.messages*', 'perm' => 'reports', 'label' => 'رسائل التواصل', 'icon' => 'send', 'badge' => \App\Models\ContactMessage::whereNull('read_at')->count()],
            ['route' => 'admin.settings', 'match' => 'admin.settings*', 'perm' => 'settings', 'label' => 'إعدادات المنصة', 'icon' => 'bolt'],
            ['route' => 'admin.content', 'match' => ['admin.content*', 'admin.pages*'], 'perm' => 'settings', 'label' => 'إدارة المحتوى', 'icon' => 'edit'],
            ['route' => 'admin.security', 'match' => 'admin.security*', 'label' => 'أمان الحساب', 'icon' => 'shield'],
        ];
        $nav = array_values(array_filter($nav, fn ($i) => ! isset($i['perm']) || auth()->user()->hasPermission($i['perm'])));
    @endphp

    <header class="site-header">
        <div class="container d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
            <a class="d-flex align-items-center gap-2 text-decoration-none" href="{{ route('admin.dashboard') }}">
                <x-logo :size="32" />
                <span class="fs-5 fw-bold text-white">{{ \App\Models\Setting::site('name') }}</span>
                <span class="liqa-badge liqa-badge--pink">لوحة الإدارة</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-secondary d-none d-md-inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm text-danger">تسجيل الخروج</button>
                </form>
            </div>
        </div>
    </header>

    <main class="container py-4 py-lg-5">
        <div class="admin-shell">
            <nav class="admin-side" aria-label="أقسام الإدارة">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" class="admin-link {{ request()->routeIs($item['match']) ? 'is-active' : '' }}">
                        <x-icon :name="$item['icon']" :size="18" /> {{ $item['label'] }}
                        @if (($item['badge'] ?? 0) > 0)<span class="count">{{ $item['badge'] }}</span>@endif
                    </a>
                @endforeach
            </nav>

            <section class="min-w-0">
                @include('partials.alerts')
                @yield('content')
            </section>
        </div>
    </main>

    @stack('scripts')
</body>
</html>
