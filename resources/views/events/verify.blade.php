@extends(auth()->check() ? 'layouts.app' : 'layouts.site')
@section('title', __('التحقق من شهادة'))
@push('head') <meta name="robots" content="noindex"> @endpush
@section('content')
<div @guest class="container py-4 py-lg-5" @endguest>
    <div class="mx-auto" style="max-width: 560px">
        <div class="form-card text-center">
            <h1 class="h3 liqa-title mb-3">{{ __('التحقق من شهادة حضور') }}</h1>
            @if ($a && $a->attended())
                <span class="liqa-badge liqa-badge--green mb-3">✓ {{ __('شهادة صحيحة') }}</span>
                <div class="d-grid gap-2 text-start mt-2" style="text-align: start">
                    <div><span class="text-secondary small d-block">{{ __('الاسم') }}</span><span class="fw-bold text-white">{{ $a->user->name }}</span></div>
                    <div><span class="text-secondary small d-block">{{ __('الحدث') }}</span><span class="fw-bold text-white">{{ $a->event->title }}</span></div>
                    <div><span class="text-secondary small d-block">{{ __('التاريخ') }}</span><span class="fw-bold text-white">{{ $a->event->starts_at->translatedFormat('l j F Y') }}</span></div>
                    <div><span class="text-secondary small d-block">{{ __('المنظّم') }}</span><span class="fw-bold text-white">{{ $a->event->host->name }}</span></div>
                    <div><span class="text-secondary small d-block">{{ __('رمز التحقق') }}</span><bdi dir="ltr" class="fw-bold text-white">{{ $code }}</bdi></div>
                </div>
            @else
                <span class="liqa-badge liqa-badge--red mb-3">✕ {{ __('لم يتم العثور على شهادة بهذا الرمز') }}</span>
                <p class="text-secondary mb-0">{{ __('تأكد من كتابة الرمز كما ورد في الشهادة.') }}</p>
            @endif
        </div>
    </div>
</div>
@endsection
