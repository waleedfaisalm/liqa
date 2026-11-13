@extends('layouts.guest')

@section('title', 'تسجيل حساب')

@section('content')
    <a href="{{ route('landing') }}" class="liqa-icon-btn mb-4" aria-label="العودة إلى الصفحة الرئيسية">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
    </a>

    <div class="mb-4">
        <div class="mb-3"><x-logo /></div>
        <h1 class="h2 liqa-title mb-2">أنشئ حسابك<br><span class="text-gradient">والتقِ بمتخصصين من مجالك</span></h1>
        <p class="text-secondary mb-0">لقاءات حقيقية منظّمة بسهولة للفريلانسرز الذين يعملون بمفردهم.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" novalidate class="d-grid gap-3">
        @csrf

        <div>
            <label class="liqa-label d-block" for="name">الاسم</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus
                   class="form-control @error('name') is-invalid @enderror" placeholder="اسمك الكامل">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="liqa-label d-block" for="email">البريد الإلكتروني</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required dir="ltr"
                   class="form-control text-end @error('email') is-invalid @enderror" placeholder="name@email.com">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="liqa-label d-block" for="password">كلمة السر</label>
            <input id="password" type="password" name="password" autocomplete="new-password" required
                   class="form-control @error('password') is-invalid @enderror" placeholder="٨ أحرف على الأقل">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-liqa mt-2">إنشاء حساب</button>
    </form>

    <div class="liqa-panel d-flex gap-3 align-items-start mt-4">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#38BDF8" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="flex-shrink-0 mt-1"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
        <p class="small text-secondary mb-0">سنرسل إليك رسالة لتفعيل بريدك. إن لم تجدها في صندوق الوارد، يرجى مراجعة مجلد الرسائل غير المرغوب فيها (Spam).</p>
    </div>

    <p class="text-center text-secondary mt-4 mb-0">
        لديك حساب؟ <a href="{{ route('login') }}" class="fw-bold text-decoration-none">سجّل الدخول</a>
    </p>
@endsection
