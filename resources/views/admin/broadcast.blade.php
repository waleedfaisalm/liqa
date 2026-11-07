@extends('layouts.admin')

@section('title', 'إشعار عام')

@section('content')
    <div class="mb-4">
        <h1 class="h2 liqa-title mb-1">إشعار عام</h1>
        <p class="text-secondary mb-0">أرسل إعلانًا قصيرًا يظهر في إشعارات جميع الأعضاء الفعّالين ({{ $audience }} عضوًا).</p>
    </div>

    <div class="liqa-card p-3 p-md-4" style="max-width: 640px">
        <form method="POST" action="{{ route('admin.broadcast.store') }}" onsubmit="return confirm('إرسال هذا الإشعار إلى جميع الأعضاء؟ لا يمكن التراجع عنه.')">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="message">نص الإشعار</label>
                <textarea id="message" name="message" rows="3" maxlength="200" class="form-control @error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
                @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="target">عند الضغط على الإشعار يفتح (اختياري)</label>
                <select id="target" name="target" class="form-select @error('target') is-invalid @enderror">
                    <option value="">بدون صفحة — إشعار نصي فقط</option>
                    @foreach ($targets as $key => [$label])
                        <option value="{{ $key }}" @selected(old('target') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('target') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <button class="btn btn-liqa px-4">إرسال إلى الجميع</button>
        </form>
    </div>
@endsection
