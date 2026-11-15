@extends('layouts.admin')

@section('title', 'الإحصاءات')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 liqa-title mb-1">الإحصاءات</h1>
            <p class="text-secondary mb-0">نمو المنصة ونشاط الأحداث والحضور، مع مقارنة بالفترة السابقة.</p>
        </div>
        <div class="tab-pills" role="tablist">
            <a href="{{ route('admin.stats', ['range' => 30]) }}" class="tab-pill {{ $range === 30 ? 'is-active' : '' }}">٣٠ يومًا</a>
            <a href="{{ route('admin.stats', ['range' => 90]) }}" class="tab-pill {{ $range === 90 ? 'is-active' : '' }}">٩٠ يومًا</a>
        </div>
    </div>

    {{-- المؤشرات --}}
    <div class="row g-3 mb-4">
        @foreach ($kpis as $k)
            @php
                $delta = $k['prev'] > 0 ? round(($k['value'] - $k['prev']) / $k['prev'] * 100) : null;
            @endphp
            <div class="col-6 col-lg-3">
                <div class="stat-tile h-100">
                    <div class="stat-tile__num">{{ $k['value'] }}</div>
                    <div class="stat-tile__label">{{ $k['label'] }} خلال {{ $range }} يومًا</div>
                    <div class="small mt-1 {{ $delta === null ? 'text-secondary' : ($delta >= 0 ? 'text-success' : 'text-danger') }}">
                        @if ($delta === null)
                            {{ $k['value'] > 0 ? 'جديد مقارنة بالفترة السابقة' : '—' }}
                        @else
                            {{ $delta >= 0 ? '▲' : '▼' }} {{ abs($delta) }}٪ عن الفترة السابقة
                        @endif
                    </div>
                    <div class="small text-secondary">{{ $k['totalLabel'] }}: {{ $k['total'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- الرسوم اليومية --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="liqa-card p-3 p-md-4">
                <h2 class="h5 liqa-title mb-1">النمو</h2>
                <p class="small text-secondary mb-3">الأعضاء الجدد والأحداث المنشأة يوميًا.</p>
                @include('admin.partials.line-chart', ['title' => 'النمو اليومي', 'labels' => $daily['labels'], 'series' => $daily['growth']])
            </div>
        </div>
        <div class="col-12">
            <div class="liqa-card p-3 p-md-4">
                <h2 class="h5 liqa-title mb-1">طلبات الانضمام</h2>
                <p class="small text-secondary mb-3">الطلبات المقدّمة مقابل المقبول منها يوميًا.</p>
                @include('admin.partials.line-chart', ['title' => 'طلبات الانضمام اليومية', 'labels' => $daily['labels'], 'series' => $daily['requests']])
            </div>
        </div>
    </div>

    {{-- جودة الأحداث والحضور --}}
    <div class="row g-3 mb-4">
        @php
            $q = $quality;
            $tiles = [
                ['v' => $q['avgFill'] !== null ? $q['avgFill'].'٪' : '—', 'l' => 'متوسط امتلاء المقاعد'],
                ['v' => $q['avgAttendees'] ?? '—', 'l' => 'متوسط الحضور لكل حدث'],
                ['v' => $q['cancelRate'].'٪', 'l' => 'نسبة الأحداث الملغاة'],
                ['v' => $q['emptyEvents'].' / '.$q['pastEvents'], 'l' => 'أحداث منتهية بلا حضور'],
                ['v' => $q['avgRating'] ?? '—', 'l' => 'متوسط تقييم الأعضاء'],
                ['v' => $q['openReports'], 'l' => 'بلاغات مفتوحة'],
            ];
        @endphp
        @foreach ($tiles as $t)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-tile h-100"><div class="stat-tile__num" style="font-size:1.7rem">{{ $t['v'] }}</div><div class="stat-tile__label">{{ $t['l'] }}</div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="liqa-card p-3 p-md-4 h-100">
                <h2 class="h5 liqa-title mb-1">مسار نشاط الأعضاء</h2>
                <p class="small text-secondary mb-3">كم عضوًا وصل إلى كل مرحلة.</p>
                @include('admin.partials.bars', ['rows' => $funnel, 'color' => 'var(--series-1)'])
            </div>
        </div>
        <div class="col-lg-6">
            <div class="liqa-card p-3 p-md-4 h-100">
                <h2 class="h5 liqa-title mb-1">الأعضاء النشطون</h2>
                <p class="small text-secondary mb-3">بحسب آخر ظهور للعضو في المنصة.</p>
                @include('admin.partials.bars', ['rows' => $active, 'color' => 'var(--series-2)'])
                <h2 class="h6 liqa-title mt-4 mb-2">الأعضاء حسب التخصص</h2>
                @include('admin.partials.bars', ['rows' => $bySpec, 'color' => 'var(--series-3)'])
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="liqa-card p-3 p-md-4 h-100">
                <h2 class="h5 liqa-title mb-3">الأحداث حسب المجال</h2>
                @include('admin.partials.bars', ['rows' => $types, 'color' => 'var(--series-1)'])
                <h2 class="h6 liqa-title mt-4 mb-2">حضوري وعن بُعد</h2>
                @include('admin.partials.bars', ['rows' => $modes, 'color' => 'var(--series-2)'])
            </div>
        </div>
        <div class="col-lg-6">
            <div class="liqa-card p-3 p-md-4 h-100">
                <h2 class="h5 liqa-title mb-3">أحداث بحسب يوم الأسبوع</h2>
                @include('admin.partials.bars', ['rows' => $weekdays, 'color' => 'var(--series-3)'])
                <h2 class="h6 liqa-title mt-4 mb-2">أكثر المدن نشاطًا</h2>
                @include('admin.partials.bars', ['rows' => $cities, 'color' => 'var(--series-1)'])
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="liqa-card p-3 p-md-4 h-100">
                <h2 class="h5 liqa-title mb-3">حالات طلبات الانضمام</h2>
                @include('admin.partials.bars', ['rows' => $statusRows, 'color' => 'var(--series-2)'])
            </div>
        </div>
        <div class="col-lg-6">
            <div class="liqa-card p-3 p-md-4 h-100">
                <h2 class="h5 liqa-title mb-3">الغرف المباشرة</h2>
                <div class="row g-3">
                    <div class="col-6"><div class="stat-tile"><div class="stat-tile__num" style="font-size:1.7rem">{{ $room['events'] }}</div><div class="stat-tile__label">أحداث استخدمت الغرفة</div></div></div>
                    <div class="col-6"><div class="stat-tile"><div class="stat-tile__num" style="font-size:1.7rem">{{ $room['admitted'] }}</div><div class="stat-tile__label">دخول مقبول</div></div></div>
                    <div class="col-6"><div class="stat-tile"><div class="stat-tile__num" style="font-size:1.7rem">{{ $room['viaLink'] }}</div><div class="stat-tile__label">دخلوا بالرابط</div></div></div>
                    <div class="col-6"><div class="stat-tile"><div class="stat-tile__num" style="font-size:1.7rem">{{ $room['removed'] }}</div><div class="stat-tile__label">أُخرجوا من الغرفة</div></div></div>
                </div>
                <p class="small text-secondary mt-3 mb-0">يُحسب من طلبات الدخول المسجّلة (غير المنظّم). لا تُسجَّل مدة الاجتماع.</p>
            </div>
        </div>
    </div>

    <div class="liqa-card p-3 p-md-4 mb-4">
        <h2 class="h5 liqa-title mb-3">أكثر المنظّمين نشاطًا</h2>
        @if ($topHosts->isEmpty())
            <p class="text-secondary mb-0">لا توجد بيانات كافية بعد.</p>
        @else
            <div class="table-responsive">
                <table class="admin-table">
                    <thead><tr><th>المنظّم</th><th>التخصص</th><th>أحداث منشورة</th><th>التقييم</th></tr></thead>
                    <tbody>
                        @foreach ($topHosts as $h)
                            <tr>
                                <td><a href="{{ route('admin.users.show', $h) }}" class="text-decoration-none">{{ $h->name }}</a></td>
                                <td>{{ $h->specialty ?: '—' }}</td>
                                <td>{{ $h->hosted_count }}</td>
                                <td>{{ $h->avg_rating !== null ? round($h->avg_rating, 1).' ★' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-chart]').forEach(function (box) {
    var d = JSON.parse(box.querySelector('.lchart__json').textContent);
    var hit = box.querySelector('.lchart__hit'), svg = box.querySelector('svg'),
        cross = box.querySelector('.lchart__cross'), tip = box.querySelector('.lchart__tip');
    var L = +hit.dataset.l, W = +hit.dataset.w, n = +hit.dataset.n;
    function move(e) {
        var r = svg.getBoundingClientRect(), px = (e.clientX - r.left) / r.width * 640;
        var i = Math.max(0, Math.min(n - 1, Math.round((px - L) / W * (n - 1))));
        var x = L + i * W / (n - 1);
        cross.setAttribute('x1', x); cross.setAttribute('x2', x); cross.style.display = '';
        tip.innerHTML = '<b>' + d.labels[i] + '</b>' + d.series.map(function (s) {
            return '<span><i style="background:' + s.color + '"></i>' + s.name + ': ' + s.data[i] + '</span>';
        }).join('');
        tip.style.display = 'block';
        var left = x / 640 * r.width; tip.style.left = Math.min(Math.max(left, 70), r.width - 70) + 'px';
    }
    hit.addEventListener('mousemove', move); hit.addEventListener('touchmove', function (e) { move(e.touches[0]); }, {passive: true});
    hit.addEventListener('mouseleave', function () { cross.style.display = 'none'; tip.style.display = 'none'; });
});
</script>
@endpush
