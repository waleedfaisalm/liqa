<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    @include('partials.theme-init')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') — {{ \App\Models\Setting::site('name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('brand/liqa-apple-touch-icon.png') }}">
    <meta name="theme-color" content="#0a0814">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite([app()->getLocale() === 'ar' ? 'resources/css/bs-rtl.css' : 'resources/css/bs-ltr.css', 'resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="d-flex flex-column align-items-center justify-content-center text-center px-4" style="min-height: 100vh">
        <div class="display-1 fw-bold liqa-title mb-2">@yield('code')</div>
        <h1 class="h3 text-white mb-2">@yield('heading')</h1>
        <p class="text-secondary mb-4" style="max-width: 460px">@yield('message')</p>
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <a href="{{ url('/') }}" class="btn btn-liqa px-4">{{ __('العودة إلى الرئيسية') }}</a>
            <a href="javascript:history.back()" class="btn btn-liqa-outline px-4">{{ __('رجوع') }}</a>
        </div>
    </main>
</body>
</html>
