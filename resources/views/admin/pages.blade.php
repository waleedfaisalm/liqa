@extends('layouts.admin')

@section('title', 'الصفحات القانونية')

@section('content')
    <div class="mb-3">
        <h1 class="h2 liqa-title mb-1">إدارة المحتوى</h1>
        <p class="text-secondary mb-0">عدّل نص سياسة الخصوصية والشروط، وتحكّم في إظهارها أو إخفائها عن الموقع.</p>
    </div>
    @include('admin.content._tabs')

    @foreach ($pages as $key => $page)
        <div class="liqa-card p-3 p-md-4 mb-4" style="max-width: 820px">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 liqa-title mb-0">{{ $page['label'] }}</h2>
                <span class="d-flex gap-2 align-items-center">
                    <span class="liqa-badge {{ $page['enabled'] ? 'liqa-badge--green' : 'liqa-badge--red' }}">{{ $page['enabled'] ? 'ظاهرة' : 'مخفية' }}</span>
                    @if ($page['enabled']) <a href="{{ route($key) }}" target="_blank" class="small">عرض الصفحة</a> @endif
                </span>
            </div>

            <form method="POST" action="{{ route('admin.pages.update', $key) }}">
                @csrf @method('PUT')
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="enabled-{{ $key }}" name="enabled" value="1" @checked($page['enabled'])>
                    <label class="form-check-label" for="enabled-{{ $key }}">إظهار الصفحة ورابطها في تذييل الموقع</label>
                </div>

                <label class="form-label" for="content-{{ $key }}">النص</label>
                <textarea id="content-{{ $key }}" name="content" rows="14" class="form-control mb-1">{{ old('content', $page['content']) }}</textarea>
                <div class="small hint mb-3">ابدأ السطر بعلامة <code>#</code> ومسافة ليصبح عنوانًا فرعيًا، وافصل الفقرات بسطر فارغ. الرمز <code>{email}</code> يُستبدل ببريد المنصة.</div>

                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-liqa px-4" name="action" value="save">حفظ</button>
                    @if ($page['custom'])
                        <button class="btn btn-liqa-outline px-3" name="action" value="reset" onclick="return confirm('استعادة النص الافتراضي وحذف تعديلاتك؟')">استعادة النص الافتراضي</button>
                    @endif
                </div>
            </form>
        </div>
    @endforeach
@endsection
