<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>تسجيل دخول الإدارة — {{ config('app.name') }}</title>
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
                <h1 class="h3 liqa-title mb-1">التحقق بخطوتين</h1>
                <p class="text-secondary mb-4">أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة، أو أحد رموز الاسترداد.</p>

                @if ($errors->any())
                    <div class="liqa-alert liqa-alert--err mb-3" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('admin.two-factor.store') }}" novalidate class="d-grid gap-3">
                    @csrf
                    <div>
                        <label class="liqa-label d-block" for="code">رمز التحقق</label>
                        <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus dir="ltr"
                               class="form-control text-center" placeholder="123456" style="letter-spacing:.3em">
                    </div>
                    <button type="submit" class="btn btn-liqa py-2">تأكيد ودخول</button>
                    <a href="{{ route('admin.login') }}" class="text-center small">العودة إلى تسجيل الدخول</a>
                </form>
            </div>
        </main>
    </div>

    
</body>
</html>
