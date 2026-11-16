@extends('layouts.guest')
@section('title', __('نسيت كلمة السر'))
@section('content')
    <a href="{{ route('login') }}" class="liqa-icon-btn mb-4" aria-label="{{ __('العودة إلى تسجيل الدخول') }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
    </a>

    <div class="mb-4">
        <span class="liqa-icon-tile mb-3">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#C084FC" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3.5 7.5l8.5 6 8.5-6"/></svg>
        </span>
        <h1 class="h2 liqa-title mb-2">{{ __('نسيت كلمة السر؟') }}</h1>
        <p class="text-secondary mb-0">{{ __('أدخل بريدك الإلكتروني وسنرسل إليك رابطًا لتعيين كلمة سر جديدة.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" novalidate class="d-grid gap-3">
        @csrf

        <div>
            <label class="liqa-label d-block" for="email">{{ __('البريد الإلكتروني') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus dir="ltr"
                   class="form-control text-end @error('email') is-invalid @enderror" placeholder="name@email.com">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-liqa">{{ __('إرسال رابط الاستعادة') }}</button>
    </form>

    <div class="liqa-panel d-flex gap-3 align-items-start mt-4">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#38BDF8" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="flex-shrink-0 mt-1"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
        <p class="small text-secondary mb-0">{{ __('إن لم تصلك الرسالة خلال دقائق، يرجى مراجعة مجلد الرسائل غير المرغوب فيها (Spam)، وإن وجدتها هناك فاختر «ليست رسالة غير مرغوب فيها».') }}</p>
    </div>

    <div class="liqa-panel d-flex gap-3 align-items-start mt-4">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#38BDF8" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="flex-shrink-0 mt-1"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
        <p class="small text-secondary mb-0">{{ __('الرابط صالح لمدة ٦٠ دقيقة فقط. إن لم يصلك، يرجى مراجعة مجلد الرسائل غير المرغوب فيها (Spam).') }}</p>
    </div>
@endsection
