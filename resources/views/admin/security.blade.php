@extends('layouts.admin')

@section('title', 'أمان الحساب')

@push('styles')
    @vite('resources/js/admin-qr.js')
@endpush

@section('content')
    <div class="mb-4">
        <h1 class="h2 liqa-title mb-1">أمان الحساب</h1>
        <p class="text-secondary mb-0">أضف خطوة تحقق ثانية عند دخولك إلى لوحة الإدارة، فلا يكفي معرفة كلمة السر وحدها.</p>
    </div>

    @if ($recoveryCodes)
        <div class="liqa-card p-3 p-md-4 mb-4" style="max-width: 680px">
            <h2 class="h5 liqa-title mb-2">رموز الاسترداد</h2>
            <p class="small text-secondary">احفظها في مكان آمن الآن؛ لن تظهر مرة أخرى. كل رمز يُستخدم مرة واحدة إذا فقدت هاتفك.</p>
            <div class="recovery-codes">
                @foreach ($recoveryCodes as $c) <code>{{ $c }}</code> @endforeach
            </div>
        </div>
    @endif

    <div class="liqa-card p-3 p-md-4" style="max-width: 680px">
        @if ($enabled)
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="liqa-badge liqa-badge--green">مفعّل</span>
                <span class="text-secondary small">رموز الاسترداد المتبقية: {{ $remaining }}</span>
            </div>

            <form method="POST" action="{{ route('admin.security.regenerate') }}" class="mb-4">
                @csrf
                <h2 class="h6 text-white">إنشاء رموز استرداد جديدة</h2>
                <div class="d-flex flex-wrap gap-2">
                    <input type="password" name="password" class="form-control" style="max-width:260px" placeholder="كلمة السر للتأكيد" required autocomplete="current-password">
                    <button class="btn btn-liqa-outline px-3">إنشاء</button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.security.disable') }}" onsubmit="return confirm('إيقاف التحقق بخطوتين؟ سيقل مستوى حماية حسابك.')">
                @csrf @method('DELETE')
                <h2 class="h6 text-white">إيقاف التحقق بخطوتين</h2>
                <div class="d-flex flex-wrap gap-2">
                    <input type="password" name="password" class="form-control" style="max-width:260px" placeholder="كلمة السر للتأكيد" required autocomplete="current-password">
                    <button class="btn btn-liqa-danger px-3">إيقاف</button>
                </div>
            </form>
            @error('password') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
        @else
            <ol class="mb-4 ps-3">
                <li class="mb-2">ثبّت تطبيق مصادقة على هاتفك، مثل Google Authenticator أو Microsoft Authenticator.</li>
                <li class="mb-2">امسح الرمز التالي بالتطبيق، أو أدخل المفتاح يدويًا.</li>
                <li>أدخل الرمز المكوّن من 6 أرقام الذي يعرضه التطبيق لتأكيد التفعيل.</li>
            </ol>

            <div class="d-flex flex-wrap gap-4 align-items-center mb-4">
                <div class="qr-box"><canvas id="qr" data-uri="{{ $uri }}" width="180" height="180"></canvas></div>
                <div>
                    <div class="small text-secondary mb-1">المفتاح اليدوي</div>
                    <code dir="ltr" class="fs-6 text-white user-select-all">{{ $secret }}</code>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.security.enable') }}" class="d-flex flex-wrap gap-2">
                @csrf
                <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" dir="ltr" class="form-control text-center @error('code') is-invalid @enderror" style="max-width:200px" placeholder="123456" required>
                <button class="btn btn-liqa px-4">تفعيل</button>
                @error('code') <div class="text-danger small w-100">{{ $message }}</div> @enderror
            </form>
        @endif
    </div>
@endsection
