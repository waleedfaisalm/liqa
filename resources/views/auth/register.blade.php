@extends('layouts.guest')
@section('title', __('تسجيل حساب'))
@section('content')
    <a href="{{ route('landing') }}" class="liqa-icon-btn mb-4" aria-label="{{ __('العودة إلى الصفحة الرئيسية') }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
    </a>

    <div class="mb-4">
        <div class="mb-3"><x-logo /></div>
        <h1 class="h2 liqa-title mb-2">{{ __('أنشئ حسابك') }}<br><span class="text-gradient">{{ __('والتقِ بمتخصصين من مجالك') }}</span></h1>
        <p class="text-secondary mb-0">{{ __('لقاءات حقيقية منظّمة بسهولة للفريلانسرز الذين يعملون بمفردهم.') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}" novalidate class="d-grid gap-3">
        @csrf

        <div>
            <label class="liqa-label d-block" for="name">{{ __('الاسم') }}</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus
                   class="form-control @error('name') is-invalid @enderror" placeholder="{{ __('اسمك الكامل') }}">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="liqa-label d-block" for="email">{{ __('البريد الإلكتروني') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required dir="ltr"
                   class="form-control text-end @error('email') is-invalid @enderror" placeholder="name@email.com">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="liqa-label d-block" for="password">{{ __('كلمة السر') }}</label>
            <input id="password" type="password" name="password" autocomplete="new-password" required
                   class="form-control @error('password') is-invalid @enderror" placeholder="{{ __('٨ أحرف على الأقل') }}">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-liqa mt-2">{{ __('إنشاء حساب') }}</button>
    </form>

    @if (\App\Http\Controllers\Auth\GoogleController::enabled())
        <div class="d-flex align-items-center gap-3 my-3 text-secondary small"><hr class="flex-grow-1 m-0"><span>{{ __('أو') }}</span><hr class="flex-grow-1 m-0"></div>
        <a href="{{ route('google.redirect') }}" class="btn btn-liqa-outline w-100 d-flex align-items-center justify-content-center gap-2">
            <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.5 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.6 5.9c4.4-4.1 7-10.1 7-17.6z"/><path fill="#FBBC05" d="M10.5 28.7c-.5-1.5-.8-3-.8-4.7s.3-3.2.8-4.7l-7.9-6.1C.9 16.4 0 20.1 0 24s.9 7.6 2.6 10.8l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.6-5.9c-2.1 1.4-4.9 2.3-8.3 2.3-6.3 0-11.6-4-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48z"/></svg>
            {{ __('المتابعة بحساب Google') }}
        </a>
    @endif

    <div class="liqa-panel d-flex gap-3 align-items-start mt-4">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#38BDF8" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="flex-shrink-0 mt-1"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
        <p class="small text-secondary mb-0">{{ __('سنرسل إليك رسالة لتفعيل بريدك. إن لم تجدها في صندوق الوارد، يرجى مراجعة مجلد الرسائل غير المرغوب فيها (Spam).') }}</p>
    </div>

    <p class="text-center text-secondary mt-4 mb-0">
        {{ __('لديك حساب؟') }} <a href="{{ route('login') }}" class="fw-bold text-decoration-none">{{ __('سجّل الدخول') }}</a>
    </p>
@endsection
