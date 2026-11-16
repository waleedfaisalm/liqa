<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>تسجيل دخول الإدارة — {{ \App\Models\Setting::site('name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('brand/liqa-apple-touch-icon.png') }}">
    <meta name="theme-color" content="#0a0814">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/bs-rtl.css', 'resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="admin-login">
        {{-- اللوحة التعريفية (تظهر يمينًا في الشاشات الكبيرة) --}}
        <aside class="admin-login__brand" aria-hidden="true">
            <div class="admin-login__brand-inner">
                <span class="admin-login__logo"><x-logo :size="64" /></span>
                <div class="admin-login__name">لقاء</div>
                <div class="admin-login__rule"></div>
                <div class="admin-login__tag">لوحة الإدارة</div>
                <p class="admin-login__lead">إدارة المستخدمين والأحداث والبلاغات في مكان واحد.</p>
            </div>
        </aside>

        {{-- نموذج الدخول --}}
        <main class="admin-login__panel">
            <div class="admin-login__card">
                <h1 class="h3 liqa-title mb-1">تسجيل دخول الإدارة</h1>
                <p class="text-secondary mb-4">هذه الصفحة مخصّصة لمديري المنصة فقط.</p>

                @if (session('status'))
                    <div class="liqa-alert liqa-alert--ok mb-3" role="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="liqa-alert liqa-alert--err mb-3" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('admin.login.store') }}" novalidate class="d-grid gap-3">
                    @csrf

                    <div>
                        <label class="liqa-label d-block" for="email">البريد الإلكتروني</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus dir="ltr"
                               class="form-control text-end" placeholder="name@email.com">
                    </div>

                    <div>
                        <label class="liqa-label d-block" for="password">كلمة السر</label>
                        <div class="position-relative">
                            <input id="password" type="password" name="password" autocomplete="current-password" required
                                   class="form-control" placeholder="••••••••" style="padding-inline-start: 2.75rem">
                            <button type="button" id="toggle-password" class="admin-eye" aria-label="إظهار كلمة السر">
                                <x-icon name="eye" :size="18" />
                            </button>
                        </div>
                    </div>

                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                        <label class="form-check-label small" for="remember">تذكّرني على هذا الجهاز</label>
                    </div>

                    <button type="submit" class="btn btn-liqa py-2">تسجيل الدخول</button>
                </form>

                <div class="d-flex justify-content-between align-items-center mt-4 small">
                    <a href="{{ route('password.request') }}" class="text-secondary text-decoration-none">نسيت كلمة السر؟</a>
                    <a href="{{ route('landing') }}" class="text-secondary text-decoration-none">العودة إلى الموقع ←</a>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('toggle-password').addEventListener('click', function () {
            var input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    </script>
</body>
</html>
