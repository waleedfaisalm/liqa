<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    @include('partials.theme-init')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'لقاء') — {{ __(\App\Models\Setting::site('name')) }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('brand/liqa-apple-touch-icon.png') }}">
    <meta name="theme-color" content="#0a0814">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite([app()->getLocale() === 'ar' ? 'resources/css/bs-rtl.css' : 'resources/css/bs-ltr.css', 'resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    @include('partials.announcement')
    {{-- شاشات الدخول والتسجيل: من غير شريط التنقل السفلي --}}
    <div class="d-flex justify-content-end gap-2 px-4 pt-3">@include('partials.lang-switch') @include('partials.theme-switch')</div>
    <main class="liqa-shell liqa-shell--guest px-4 pt-3">
        @include('partials.alerts')
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
