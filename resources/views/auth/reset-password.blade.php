@extends('layouts.guest')
@section('title', __('كلمة سر جديدة'))
@section('content')
    <div class="mb-4 mt-4">
        <span class="liqa-icon-tile mb-3">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#C084FC" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2.5"/><path d="M8 11V8a4 4 0 018 0v3"/></svg>
        </span>
        <h1 class="h2 liqa-title mb-2">{{ __('كلمة سر جديدة') }}</h1>
        <p class="text-secondary mb-0">{{ __('اختر كلمة سر جديدة لحسابك (٨ أحرف على الأقل).') }}</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" novalidate class="d-grid gap-3">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label class="liqa-label d-block" for="email">{{ __('البريد الإلكتروني') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required dir="ltr"
                   class="form-control text-end @error('email') is-invalid @enderror">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="liqa-label d-block" for="password">{{ __('كلمة السر الجديدة') }}</label>
            <input id="password" type="password" name="password" autocomplete="new-password" required autofocus
                   class="form-control @error('password') is-invalid @enderror">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="liqa-label d-block" for="password_confirmation">{{ __('تأكيد كلمة السر') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required
                   class="form-control">
        </div>

        <button type="submit" class="btn btn-liqa mt-2">{{ __('حفظ كلمة السر') }}</button>
    </form>
@endsection
