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

            <hr class="my-4">
            <h2 class="h5 liqa-title mb-1">الأتمتة الآلية</h2>
            <p class="small hint mb-3">قواعد تعمل تلقائيًا في الخلفية. لكل قاعدة مفتاح تشغيل وحدّ قابل للتعديل.</p>

            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="auto_report_alert" name="auto_report_alert" value="1" @checked(old('auto_report_alert', $auto['auto_report_alert'] === '1'))>
                <label class="form-check-label" for="auto_report_alert">تنبيه عاجل عند تكرار البلاغات على الطرف نفسه</label>
            </div>
            <div class="mb-4 ps-4">
                <label class="form-label small" for="auto_report_threshold">عدد البلاغات المفتوحة (من مبلِّغين مختلفين) الذي يُطلق التنبيه</label>
                <input type="number" id="auto_report_threshold" name="auto_report_threshold" min="2" max="10" class="form-control @error('auto_report_threshold') is-invalid @enderror" style="max-width: 8rem" value="{{ old('auto_report_threshold', $auto['auto_report_threshold']) }}">
                @error('auto_report_threshold') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="auto_reactivation" name="auto_reactivation" value="1" @checked(old('auto_reactivation', $auto['auto_reactivation'] === '1'))>
                <label class="form-check-label" for="auto_reactivation">رسالة إعادة تنشيط للأعضاء الغائبين</label>
                <div class="small hint">إشعار وبريد مع اللقاءات القادمة، مرة واحدة كل 60 يومًا كحد أدنى لكل عضو، ولمن فعّل الإشعارات فقط.</div>
            </div>
            <div class="mb-4 ps-4">
                <label class="form-label small" for="auto_reactivation_days">عدد أيام الغياب قبل الإرسال</label>
                <input type="number" id="auto_reactivation_days" name="auto_reactivation_days" min="7" max="180" class="form-control @error('auto_reactivation_days') is-invalid @enderror" style="max-width: 8rem" value="{{ old('auto_reactivation_days', $auto['auto_reactivation_days']) }}">
                @error('auto_reactivation_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="auto_fake_detection" name="auto_fake_detection" value="1" @checked(old('auto_fake_detection', $auto['auto_fake_detection'] === '1'))>
                <label class="form-check-label" for="auto_fake_detection">كشف الحسابات الوهمية عند التسجيل</label>
                <div class="small hint">يقيّم كل حساب جديد بدرجة خطورة من 100 (بريد مؤقت، أنماط غريبة، تسجيلات متكررة من شبكة واحدة). لا يمنع التسجيل ولا يوقف أحدًا تلقائيًا.</div>
            </div>
            <div class="mb-4 ps-4">
                <label class="form-label small" for="auto_risk_threshold">درجة الخطورة التي تُرسل تنبيهًا للمشرفين</label>
                <input type="number" id="auto_risk_threshold" name="auto_risk_threshold" min="30" max="95" class="form-control @error('auto_risk_threshold') is-invalid @enderror" style="max-width: 8rem" value="{{ old('auto_risk_threshold', $auto['auto_risk_threshold']) }}">
                @error('auto_risk_threshold') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label class="form-label" for="auto_rating_delay">إرسال طلب التقييم بعد انتهاء اللقاء بـ (دقيقة)</label>
                <input type="number" id="auto_rating_delay" name="auto_rating_delay" min="0" max="1440" class="form-control @error('auto_rating_delay') is-invalid @enderror" style="max-width: 8rem" value="{{ old('auto_rating_delay', $auto['auto_rating_delay']) }}">
                @error('auto_rating_delay') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="small hint mt-1">الافتراضي 60 دقيقة. يصل الطلب إشعارًا وبريدًا للمشاركين.</div>
            </div>

            <button class="btn btn-liqa px-4">حفظ الإعدادات</button>
        </form>
    </div>
@endsection
