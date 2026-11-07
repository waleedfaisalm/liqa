@extends('layouts.admin')

@section('title', 'نصوص الصفحة الرئيسية')

@php
    $sections = [
        'hero' => ['الواجهة الرئيسية', ['eyebrow' => 'الشارة العلوية', 'title' => 'العنوان (السطر الأول)', 'title_accent' => 'العنوان (السطر الملوّن)', 'lead' => 'الوصف', 'hint_1' => 'ميزة 1 (تحت الأزرار)', 'hint_2' => 'ميزة 2 (تحت الأزرار)']],
        'problem' => ['قسم المشكلة', ['eyebrow' => 'الشارة', 'title' => 'العنوان', 'title_accent' => 'العنوان الملوّن', 'subtitle' => 'الوصف الفرعي']],
        'how' => ['قسم الحل (الخطوات)', ['eyebrow' => 'الشارة', 'title' => 'العنوان', 'title_accent' => 'العنوان الملوّن', 'subtitle' => 'الوصف الفرعي']],
        'why' => ['قسم «لماذا لقاء؟»', ['eyebrow' => 'الشارة', 'title' => 'العنوان', 'title_accent' => 'العنوان الملوّن']],
        'cta_guest' => ['الدعوة الأخيرة — للزائر', ['title' => 'العنوان', 'title_accent' => 'العنوان الملوّن', 'text' => 'النص']],
        'cta_member' => ['الدعوة الأخيرة — للعضو', ['title' => 'العنوان', 'title_accent' => 'العنوان الملوّن', 'text' => 'النص']],
    ];
    $cardSets = ['problem' => 'بطاقات المشكلة', 'how' => 'بطاقات الخطوات', 'why' => 'بطاقات المزايا'];
@endphp

@section('content')
    <div class="mb-3">
        <h1 class="h2 liqa-title mb-1">إدارة المحتوى</h1>
        <p class="text-secondary mb-0">عدّل نصوص الصفحة الرئيسية. الحقل الفارغ يعود إلى النص الافتراضي.</p>
    </div>
    @include('admin.content._tabs')

    @error('landing') <div class="liqa-alert liqa-alert--err mb-3">{{ $message }}</div> @enderror

    <form method="POST" action="{{ route('admin.content.landing.update') }}" style="max-width: 820px">
        @csrf @method('PUT')

        @foreach ($sections as $sec => [$title, $fields])
            <div class="liqa-card p-3 p-md-4 mb-3">
                <h2 class="h5 liqa-title mb-3">{{ $title }}</h2>
                <div class="row g-3">
                    @foreach ($fields as $key => $label)
                        <div class="{{ in_array($key, ['lead', 'subtitle', 'text']) ? 'col-12' : 'col-md-6' }}">
                            <label class="form-label small" for="l-{{ $sec }}-{{ $key }}">{{ $label }}</label>
                            @if (in_array($key, ['lead', 'subtitle', 'text']))
                                <textarea id="l-{{ $sec }}-{{ $key }}" name="landing[{{ $sec }}][{{ $key }}]" rows="2" maxlength="300" class="form-control">{{ $L[$sec][$key] }}</textarea>
                            @else
                                <input id="l-{{ $sec }}-{{ $key }}" name="landing[{{ $sec }}][{{ $key }}]" value="{{ $L[$sec][$key] }}" maxlength="300" class="form-control">
                            @endif
                        </div>
                    @endforeach
                </div>

                @if (isset($cardSets[$sec]))
                    <h3 class="h6 text-white mt-4 mb-2">{{ $cardSets[$sec] }}</h3>
                    @foreach ($L[$sec]['cards'] as $i => $card)
                        <div class="row g-2 mb-2 align-items-start">
                            <div class="col-md-4"><input name="landing[{{ $sec }}][cards][{{ $i }}][title]" value="{{ $card['title'] }}" maxlength="300" class="form-control" aria-label="عنوان البطاقة {{ $i + 1 }}"></div>
                            <div class="col-md-8"><textarea name="landing[{{ $sec }}][cards][{{ $i }}][text]" rows="2" maxlength="300" class="form-control" aria-label="نص البطاقة {{ $i + 1 }}">{{ $card['text'] }}</textarea></div>
                        </div>
                    @endforeach
                @endif
            </div>
        @endforeach

        <div class="d-flex flex-wrap gap-2 mb-4">
            <button class="btn btn-liqa px-4" name="action" value="save">حفظ النصوص</button>
            <a href="{{ route('landing') }}" target="_blank" class="btn btn-liqa-outline px-3">معاينة الصفحة</a>
            @if ($custom)
                <button class="btn btn-liqa-outline px-3" name="action" value="reset" onclick="return confirm('استعادة كل النصوص الافتراضية وحذف تعديلاتك؟')">استعادة الافتراضي</button>
            @endif
        </div>
    </form>
@endsection
