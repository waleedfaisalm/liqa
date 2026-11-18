<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    @include('partials.theme-init')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'لقاء')</title>
    <meta name="description" content="@yield('description', __('لقاء: منصة لقاءات للفريلانسر والعاملين لحسابهم الخاص في السعودية والوطن العربي.'))">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('brand/liqa-apple-touch-icon.png') }}">
    <meta name="theme-color" content="#0a0814">
    @include('partials.seo')
    @stack('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite([app()->getLocale() === 'ar' ? 'resources/css/bs-rtl.css' : 'resources/css/bs-ltr.css', 'resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('partials.announcement')
    <header class="site-header">
        <nav class="navbar navbar-expand-md py-2" aria-label="{{ __('القائمة الرئيسية') }}">
            <div class="container">
                <a href="{{ route('landing') }}" class="navbar-brand d-flex align-items-center gap-2 text-decoration-none m-0">
                    <x-logo :size="40" />
                    <span class="fs-4 fw-bold text-white">{{ __(\App\Models\Setting::site('name')) }}</span>
                </a>

                <div class="d-flex align-items-center gap-2 d-md-none ms-auto me-2">
                    <a href="{{ route('register') }}" class="btn btn-liqa btn-sm px-3 d-flex align-items-center text-nowrap">{{ __('إنشاء حساب') }}</a>
                </div>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#guestNav"
                        aria-controls="guestNav" aria-expanded="false" aria-label="{{ __('فتح القائمة') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="guestNav">
                    <ul class="navbar-nav gap-md-1 mt-3 mt-md-0 ms-md-auto align-items-md-center">
                        <li class="nav-item"><a href="{{ route('landing') }}" class="nav-link app-link {{ request()->routeIs('landing') ? 'is-active' : '' }}">{{ __('الرئيسية') }}</a></li>
                        <li class="nav-item"><a href="{{ route('events.index') }}" class="nav-link app-link {{ request()->routeIs('events.*') ? 'is-active' : '' }}">{{ __('الأحداث') }}</a></li>
                        <li class="nav-item"><a href="{{ route('contact') }}" class="nav-link app-link {{ request()->routeIs('contact') ? 'is-active' : '' }}">{{ __('تواصل معنا') }}</a></li>
                        <li class="nav-item mt-2 mt-md-0 ms-md-2">@include('partials.lang-switch')</li>
                        <li class="nav-item mt-2 mt-md-0 ms-md-1">@include('partials.theme-switch')</li>
                        <li class="nav-item mt-2 mt-md-0 ms-md-2"><a href="{{ route('login') }}" class="btn btn-liqa-outline px-3 d-flex align-items-center justify-content-center">{{ __('تسجيل الدخول') }}</a></li>
                        <li class="nav-item d-none d-md-block"><a href="{{ route('register') }}" class="btn btn-liqa px-3 d-flex align-items-center" style="min-height:2.75rem">{{ __('إنشاء حساب') }}</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container pt-3">@include('partials.alerts')</div>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container py-4 d-flex flex-column flex-md-row gap-2 justify-content-between align-items-center">
            <span class="fw-bold">{{ __(\App\Models\Setting::site('name')) }}</span>
            <span class="small">
                <a href="{{ route('contact') }}" class="text-secondary text-decoration-none">{{ __('تواصل معنا') }}</a> ·
                @if (\App\Models\Setting::enabled('privacy_enabled')) <a href="{{ route('privacy') }}" class="text-secondary text-decoration-none">{{ __('سياسة الخصوصية') }}</a> @endif
                @if (\App\Models\Setting::enabled('privacy_enabled') && \App\Models\Setting::enabled('terms_enabled')) · @endif
                @if (\App\Models\Setting::enabled('terms_enabled')) <a href="{{ route('terms') }}" class="text-secondary text-decoration-none">{{ __('الشروط والأحكام') }}</a> @endif
            </span>
            @php $__soc = array_filter(['X' => \App\Models\Setting::get('social_x'), __('إنستغرام') => \App\Models\Setting::get('social_instagram'), __('لينكدإن') => \App\Models\Setting::get('social_linkedin')]); @endphp
            @if ($__soc)
                <span class="small">@foreach ($__soc as $__n => $__u)<a href="{{ $__u }}" target="_blank" rel="noopener noreferrer" class="text-secondary text-decoration-none ms-2">{{ $__n }}</a>@endforeach</span>
            @endif
            <span class="small text-secondary">{{ \App\Models\Setting::site('tagline') }} · {{ date('Y') }}</span>
        </div>
    </footer>
</body>
</html>
