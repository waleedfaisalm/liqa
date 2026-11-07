<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'لقاء')</title>
    <meta name="description" content="@yield('description', 'لقاء: منصة لقاءات للفريلانسرز. انشر لقاءً، قدّم طلب انضمام، والتقِ بمتخصصين من مجالك.')">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('brand/liqa-apple-touch-icon.png') }}">
    <meta name="theme-color" content="#0a0814">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('partials.announcement')
    <header class="site-header">
        <div class="container d-flex align-items-center justify-content-between py-3">
            <a href="{{ route('landing') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                <x-logo :size="40" />
                <span class="fs-4 fw-bold text-white">{{ \App\Models\Setting::site('name') }}</span>
            </a>
            <nav class="d-flex align-items-center gap-2" aria-label="القائمة الرئيسية">
                <a href="{{ route('landing') }}" class="app-link {{ request()->routeIs('landing') ? 'is-active' : '' }} d-none d-sm-inline-block text-decoration-none">الرئيسية</a>
                <a href="{{ route('events.index') }}" class="app-link {{ request()->routeIs('events.*') ? 'is-active' : '' }} d-none d-sm-inline-block text-decoration-none">الأحداث</a>
                <a href="{{ route('contact') }}" class="app-link {{ request()->routeIs('contact') ? 'is-active' : '' }} d-none d-sm-inline-block text-decoration-none">تواصل معنا</a>
                <a href="{{ route('login') }}" class="btn btn-liqa-outline px-3 d-flex align-items-center">تسجيل الدخول</a>
                <a href="{{ route('register') }}" class="btn btn-liqa px-3 d-flex align-items-center" style="min-height:2.75rem">إنشاء حساب</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container pt-3">@include('partials.alerts')</div>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container py-4 d-flex flex-column flex-md-row gap-2 justify-content-between align-items-center">
            <span class="fw-bold">{{ \App\Models\Setting::site('name') }}</span>
            <span class="small">
                <a href="{{ route('contact') }}" class="text-secondary text-decoration-none">تواصل معنا</a> ·
                @if (\App\Models\Setting::enabled('privacy_enabled')) <a href="{{ route('privacy') }}" class="text-secondary text-decoration-none">سياسة الخصوصية</a> @endif
                @if (\App\Models\Setting::enabled('privacy_enabled') && \App\Models\Setting::enabled('terms_enabled')) · @endif
                @if (\App\Models\Setting::enabled('terms_enabled')) <a href="{{ route('terms') }}" class="text-secondary text-decoration-none">الشروط والأحكام</a> @endif
            </span>
            @php $__soc = array_filter(['X' => \App\Models\Setting::get('social_x'), 'إنستغرام' => \App\Models\Setting::get('social_instagram'), 'لينكدإن' => \App\Models\Setting::get('social_linkedin')]); @endphp
            @if ($__soc)
                <span class="small">@foreach ($__soc as $__n => $__u)<a href="{{ $__u }}" target="_blank" rel="noopener noreferrer" class="text-secondary text-decoration-none ms-2">{{ $__n }}</a>@endforeach</span>
            @endif
            <span class="small text-secondary">{{ \App\Models\Setting::site('tagline') }} · {{ date('Y') }}</span>
        </div>
    </footer>
</body>
</html>
