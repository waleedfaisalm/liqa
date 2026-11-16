@extends('layouts.guest')
@section('title', __('تسجيل دخول'))
@section('content')
    <a href="{{ route('landing') }}" class="liqa-icon-btn mb-4" aria-label="{{ __('العودة إلى الصفحة الرئيسية') }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
    </a>

    <div class="mb-4">
        <div class="mb-3"><x-logo /></div>
        <h1 class="h2 liqa-title mb-2">{{ __('أهلاً بعودتك') }}<br><span class="text-gradient">{{ __('لقاءاتك بانتظارك') }}</span></h1>
        <p class="text-secondary mb-0">{{ __('سجّل دخولك واطّلع على الأحداث القادمة في مجالك.') }}</p>
    </div>

    <form method="POST" action="{{ route('login') }}" novalidate class="d-grid gap-3">
        @csrf

        <div>
            <label class="liqa-label d-block" for="email">{{ __('البريد الإلكتروني') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus dir="ltr"
                   class="form-control text-end @error('email') is-invalid @enderror" placeholder="name@email.com">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="liqa-label d-block" for="password">{{ __('كلمة السر') }}</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required
                   class="form-control @error('password') is-invalid @enderror" placeholder="{{ __('كلمة السر') }}">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                <label class="form-check-label small" for="remember">{{ __('تذكّرني') }}</label>
            </div>
            <a href="{{ route('password.request') }}" class="small fw-bold text-decoration-none">{{ __('نسيت كلمة السر؟') }}</a>
        </div>

        <button type="submit" class="btn btn-liqa mt-2">{{ __('تسجيل دخول') }}</button>
    </form>

    <p class="text-center text-secondary mt-4 mb-0">
        {{ __('ليس لديك حساب بعد؟') }} <a href="{{ route('register') }}" class="fw-bold text-decoration-none">{{ __('أنشئ حساب') }}</a>
    </p>
@endsection
