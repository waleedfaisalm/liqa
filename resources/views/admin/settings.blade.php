@extends('layouts.admin')

@section('title', 'إعدادات المنصة')

@section('content')
    <div class="mb-4">
        <h1 class="h2 liqa-title mb-1">إعدادات المنصة</h1>
        <p class="text-secondary mb-0">تحكّم في ما يستطيع الأعضاء فعله، وانشر إعلانًا يظهر في أعلى المنصة. هذه الصفحة للمالك فقط.</p>
    </div>

    <div class="liqa-card p-3 p-md-4" style="max-width: 680px">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf @method('PUT')

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="registration_enabled" name="registration_enabled" value="1" @checked(old('registration_enabled', $registration))>
                <label class="form-check-label" for="registration_enabled">السماح بتسجيل أعضاء جدد</label>
                <div class="small hint">عند الإيقاف يُمنع إنشاء حسابات جديدة، ويبقى الأعضاء الحاليون على حالهم.</div>
            </div>

            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" role="switch" id="event_creation_enabled" name="event_creation_enabled" value="1" @checked(old('event_creation_enabled', $eventCreation))>
                <label class="form-check-label" for="event_creation_enabled">السماح بإنشاء أحداث جديدة</label>
                <div class="small hint">عند الإيقاف لا يستطيع الأعضاء نشر أحداث جديدة، وتبقى الأحداث الحالية كما هي.</div>
            </div>

            <div class="mb-4">
                <label class="form-label" for="announcement">إعلان أعلى المنصة (اختياري)</label>
                <textarea id="announcement" name="announcement" rows="2" maxlength="200" class="form-control @error('announcement') is-invalid @enderror" placeholder="مثال: ستخضع المنصة لصيانة قصيرة مساء الخميس.">{{ old('announcement', $announcement) }}</textarea>
                @error('announcement') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="small hint mt-1">يظهر لجميع الزوّار والأعضاء. اتركه فارغًا لإخفائه.</div>
            </div>

            <button class="btn btn-liqa px-4">حفظ الإعدادات</button>
        </form>
    </div>
@endsection
