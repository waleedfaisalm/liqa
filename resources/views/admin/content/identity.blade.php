@extends('layouts.admin')

@section('title', 'هوية المنصة')

@section('content')
    <div class="mb-3">
        <h1 class="h2 liqa-title mb-1">إدارة المحتوى</h1>
        <p class="text-secondary mb-0">تحكّم في محتوى المنصة ومظهرها العام. هذا القسم للمالك فقط.</p>
    </div>
    @include('admin.content._tabs')

    <div class="liqa-card p-3 p-md-4" style="max-width: 680px">
        <form method="POST" action="{{ route('admin.content.identity.update') }}" class="d-grid gap-3">
            @csrf @method('PUT')
            @foreach ([
                ['site_name', 'اسم المنصة', 'يظهر في الشريط العلوي وعنوان الصفحة وتذييل الموقع.', false],
                ['site_tagline', 'الشعار الفرعي (التذييل)', 'جملة قصيرة تظهر في أسفل الموقع.', false],
                ['support_email', 'بريد التواصل العام', 'يظهر في الصفحات القانونية. إن تُرك فارغًا يُستخدم بريد الإرسال الافتراضي.', true],
                ['social_x', 'رابط حساب X', 'مثال: https://x.com/اسم_الحساب', true],
                ['social_instagram', 'رابط إنستغرام', 'مثال: https://instagram.com/اسم_الحساب', true],
                ['social_linkedin', 'رابط لينكدإن', 'مثال: https://linkedin.com/company/اسم_الصفحة', true],
            ] as [$name, $label, $hint, $ltr])
                <div>
                    <label class="form-label" for="{{ $name }}">{{ $label }}</label>
                    <input id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $v[$name]) }}" @if ($ltr) dir="ltr" class="form-control text-end @error($name) is-invalid @enderror" @else class="form-control @error($name) is-invalid @enderror" @endif>
                    @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="small hint mt-1">{{ $hint }}</div>
                </div>
            @endforeach
            <button class="btn btn-liqa px-4 align-self-start">حفظ</button>
        </form>
    </div>
@endsection
