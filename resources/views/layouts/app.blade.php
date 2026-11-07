<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'لقاء') — {{ \App\Models\Setting::site('name') }}</title>
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
    @include('partials.announcement')
    @php
        $links = [
            ['route' => 'landing', 'label' => 'الرئيسية', 'match' => 'landing'],
            ['route' => 'home', 'label' => 'لوحة التحكم', 'match' => 'home'],
            ['route' => 'events.index', 'label' => 'الأحداث', 'match' => 'events.index'],
            ['route' => 'events.mine', 'label' => 'أحداثي', 'match' => 'events.mine'],
            ['route' => 'notifications', 'label' => 'الإشعارات', 'match' => 'notifications'],
            ['route' => 'contact', 'label' => 'تواصل معنا', 'match' => 'contact'],
        ];
        $user = auth()->user();
    @endphp

    <header class="site-header">
        <nav class="navbar navbar-expand-lg py-2" aria-label="القائمة الرئيسية">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="{{ route('landing') }}">
                    <x-logo :size="40" />
                    <span class="fs-4 fw-bold text-white">{{ \App\Models\Setting::site('name') }}</span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                        aria-controls="mainNav" aria-expanded="false" aria-label="فتح القائمة">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNav">
                    <div class="nav-end d-lg-flex align-items-lg-center gap-lg-3">
                    <ul class="navbar-nav gap-lg-1 mt-3 mt-lg-0">
                        @foreach ($links as $link)
                            <li class="nav-item">
                                <a class="nav-link app-link {{ request()->routeIs($link['match']) ? 'is-active' : '' }}"
                                   href="{{ route($link['route']) }}"
                                   @if (request()->routeIs($link['match'])) aria-current="page" @endif>{{ $link['label'] }}@if ($link['route'] === 'notifications' && ($unreadCount = $user->unreadNotificationsCount()) > 0)<span class="nav-badge">{{ $unreadCount }}</span>@endif</a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="dropdown mt-2 mt-lg-0">
                        <button class="btn btn-liqa-outline d-flex align-items-center gap-2 px-3" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <x-avatar :user="$user" :size="30" />
                            <span class="fw-bold">{{ $user->name }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end liqa-dropdown">
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('home') }}"><x-icon name="dashboard" :size="16" /> لوحة التحكم</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('events.mine') }}"><x-icon name="calendar" :size="16" /> أحداثي</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('saved.index') }}"><x-icon name="bookmark" :size="16" /> الأحداث المحفوظة</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('events.create') }}"><x-icon name="plus" :size="16" /> انشر حدثًا جديدًا</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('profile') }}"><x-icon name="user" :size="16" /> ملفي الشخصي</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('settings') }}"><x-icon name="edit" :size="16" /> التعديل والإعدادات</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2"><x-icon name="logout" :size="16" /> تسجيل الخروج</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    @if ($user && ! $user->hasVerifiedEmail())
        <div class="container pt-3">
            <div class="liqa-alert liqa-alert--err" role="alert">
                بريدك الإلكتروني غير مفعّل. <a href="{{ route('verification.notice') }}" class="text-white fw-bold">اضغط هنا لتفعيله</a>.
            </div>
        </div>
    @endif

    <main class="@yield('main_class', 'container py-4 py-lg-5')">
        @hasSection('main_class')
            <div class="container pt-3">@include('partials.alerts')</div>
        @else
            @include('partials.alerts')
        @endif
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container py-4 d-flex flex-column flex-md-row gap-2 justify-content-between align-items-center">
            <span class="d-flex align-items-center gap-2 fw-bold"><x-logo :size="28" /> {{ \App\Models\Setting::site('name') }}</span>
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

    @stack('scripts')
    <script>
        // حفظ/إزالة الحدث دون إعادة تحميل الصفحة (وإن فشل الطلب يُرسل النموذج عاديًا)
        document.addEventListener('submit', function (e) {
            var form = e.target.closest ? e.target.closest('.js-save-form') : null;
            if (!form) return;
            e.preventDefault();
            fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
                credentials: 'same-origin'
            }).then(function (r) {
                if (!r.ok) throw new Error('failed');
                return r.json();
            }).then(function (data) {
                var btn = form.querySelector('button');
                btn.classList.toggle('is-saved', data.saved);
                var label = data.saved ? 'إزالة من المحفوظات' : 'حفظ الحدث';
                btn.setAttribute('aria-label', label);
                btn.setAttribute('title', label);
                var txt = form.querySelector('.js-save-label');
                if (txt) txt.textContent = data.saved ? 'محفوظ — اضغط للإزالة' : 'حفظ الحدث';
            }).catch(function () { form.classList.remove('js-save-form'); form.submit(); });
        });
    </script>
</body>
</html>
