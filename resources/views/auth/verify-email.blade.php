@extends('layouts.guest')
@section('title', __('تفعيل البريد الإلكتروني'))
@section('content')
    <div class="mb-4">
        <span class="liqa-icon-tile mb-3">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#C084FC" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3.5 7.5l8.5 6 8.5-6"/></svg>
        </span>
        <h1 class="h2 liqa-title mb-2">{{ __('فعّل بريدك الإلكتروني') }}</h1>
        <p class="text-secondary mb-0">
            {{ __('أرسلنا رابط التفعيل إلى') }}
            <strong class="text-white" dir="ltr">{{ auth()->user()->email }}</strong>{{ __('. افتح بريدك واضغط على الرابط لمتابعة استخدام المنصة.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('verification.send') }}" class="d-grid gap-3">
        @csrf
        <button type="submit" class="btn btn-liqa">{{ __('إعادة إرسال رابط التفعيل') }}</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-liqa-outline w-100">{{ __('تسجيل الخروج') }}</button>
    </form>

    <div class="liqa-panel d-flex gap-3 align-items-start mt-4">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#38BDF8" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="flex-shrink-0 mt-1"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
        <p class="small text-secondary mb-0">{{ __('إن لم تجد الرسالة، يرجى مراجعة مجلد الرسائل غير المرغوب فيها (Spam).') }}</p>
    </div>
@endsection
