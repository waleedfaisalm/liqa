@extends('layouts.admin')

@section('title', 'الشرائح')

@section('content')
    <div class="mb-4">
        <h1 class="h2 liqa-title mb-1">الشرائح المحفوظة</h1>
        <p class="text-secondary mb-0">عرّف مجموعة أعضاء بشروط، واحفظها، ثم أرسل لها إشعارًا موجّهًا متى شئت. تستثني الشريحة المدراء والموقوفين، ولا يصل الإشعار إلا لمن فعّل الإشعارات.</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="liqa-card p-3 p-md-4">
                <h2 class="h5 liqa-title mb-3">شريحة جديدة</h2>
                <form method="POST" action="{{ route('admin.segments.store') }}">
                    @csrf
                    <div class="mb-3"><label class="form-label small">اسم الشريحة</label><input name="name" class="form-control" maxlength="80" required placeholder="مثال: مطوّرو عدن النشطون"></div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label small">المدينة</label><input name="city" class="form-control" list="seg-cities" maxlength="60"><datalist id="seg-cities">@foreach ($cities as $c)<option value="{{ $c }}">@endforeach</datalist></div>
                        <div class="col-6"><label class="form-label small">التخصص</label><select name="specialty" class="form-select"><option value="">الكل</option>@foreach ($specialties as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach</select></div>
                        <div class="col-6"><label class="form-label small">انضموا خلال (يوم)</label><input type="number" name="joined_within_days" min="1" class="form-control"></div>
                        <div class="col-6"><label class="form-label small">نشطون خلال (يوم)</label><input type="number" name="active_within_days" min="1" class="form-control"></div>
                        <div class="col-6"><label class="form-label small">غائبون منذ (يوم فأكثر)</label><input type="number" name="inactive_days" min="1" class="form-control"></div>
                        <div class="col-6"><label class="form-label small">حضروا لقاءات (على الأقل)</label><input type="number" name="attended_min" min="1" class="form-control"></div>
                    </div>
                    <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="verified" value="1" id="f-ver"><label class="form-check-label small" for="f-ver">الموثّقون فقط</label></div>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="never_attended" value="1" id="f-nev"><label class="form-check-label small" for="f-nev">لم يحضروا أي لقاء بعد</label></div>
                    <p class="small hint">اترك الحقول فارغة لتجاهل الشرط. بلا شروط = كل الأعضاء.</p>
                    <button class="btn btn-liqa w-100">حفظ الشريحة</button>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            @forelse ($segments as $s)
                <div class="liqa-card p-3 p-md-4 mb-3">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <h3 class="h6 text-white mb-1">{{ $s->name }}</h3>
                            <div class="small text-secondary">{{ $s->describe() }}</div>
                        </div>
                        <form method="POST" action="{{ route('admin.segments.destroy', $s) }}" onsubmit="return confirm('حذف هذه الشريحة؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">حذف</button></form>
                    </div>
                    <div class="small mb-3"><span class="liqa-badge liqa-badge--sky">{{ $s->total }} عضو</span> <span class="liqa-badge">{{ $s->reachable }} يستقبلون الإشعارات</span> @if ($s->last_sent_at) <span class="hint">آخر إرسال {{ $s->last_sent_at->diffForHumans() }}</span> @endif</div>
                    <form method="POST" action="{{ route('admin.segments.send', $s) }}" onsubmit="return confirm('إرسال الإشعار إلى {{ $s->reachable }} عضو؟')">
                        @csrf
                        <input name="message" class="form-control form-control-sm mb-2" maxlength="200" required placeholder="نص الإشعار (200 حرف)">
                        <div class="d-flex gap-2"><input name="link" class="form-control form-control-sm" maxlength="200" placeholder="رابط داخل المنصة (اختياري) مثل /events" dir="ltr"><button class="btn btn-sm btn-liqa px-3 text-nowrap" @disabled($s->reachable === 0)>إرسال</button></div>
                    </form>
                </div>
            @empty
                <div class="liqa-card p-4 text-center text-secondary">لا توجد شرائح بعد. أنشئ أول شريحة من النموذج.</div>
            @endforelse
        </div>
    </div>
@endsection
