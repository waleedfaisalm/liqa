@extends('layouts.admin')

@section('title', 'نظرة عامة')

@section('content')
    <div class="mb-4">
        <h1 class="h2 liqa-title mb-1">نظرة عامة</h1>
        <p class="text-secondary mb-0">ملخّص سريع لما يجري في المنصة.</p>
    </div>

    @php $newAlerts = auth()->user()->userNotifications()->whereNull('read_at')->whereIn('type', \App\Models\UserNotification::ADMIN_TYPES)->latest()->limit(3)->get(); @endphp
    @if ($newAlerts->isNotEmpty())
        <div class="liqa-card p-3 p-md-4 mb-4" style="border-color: rgba(248,113,113,.45)">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 text-danger mb-0">تنبيهات تحتاج مراجعتك</h2>
                <a href="{{ route('admin.alerts') }}" class="small text-decoration-none">عرض الكل ←</a>
            </div>
            @foreach ($newAlerts as $a)
                <a href="{{ route('admin.alerts.open', $a) }}" class="d-block text-decoration-none py-1 text-white">{{ $a->message }}</a>
            @endforeach
        </div>
    @endif

    <div class="liqa-card p-3 p-md-4 mb-4">
        <h2 class="h5 liqa-title mb-3 d-flex align-items-center gap-2">
            <span class="presence-dot presence-dot--inline"></span>
            المتصلون الآن ({{ $stats['online'] }})
        </h2>
        @if ($onlineUsers->isEmpty())
            <p class="text-secondary mb-0">لا يوجد أعضاء متصلون في هذه اللحظة.</p>
        @else
            <div class="d-flex flex-wrap gap-2">
                @foreach ($onlineUsers as $u)
                    <a href="{{ route('admin.users', ['q' => $u->email]) }}" class="request-row text-decoration-none py-2">
                        <span class="avatar-wrap">
                            <x-avatar :user="$u" :size="32" />
                            <span class="presence-dot"></span>
                        </span>
                        <span>
                            <span class="d-block fw-bold text-white small">{{ $u->name }}</span>
                            <span class="d-block small text-secondary">{{ $u->last_seen_at->diffForHumans() }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
        <p class="small hint mt-3 mb-0">يُعدّ العضو متصلًا إذا تصفّح الموقع خلال آخر {{ \App\Models\User::ONLINE_MINUTES }} دقائق. تتحدّث الصفحة تلقائيًا كل دقيقة.</p>
    </div>

    @php
        $tiles = [
            ['المستخدمون', $stats['users'], '+'.$stats['users_week'].' هذا الأسبوع'],
            ['أحداث قادمة', $stats['events_upcoming'], 'من '.$stats['events_total'].' حدثًا'],
            ['طلبات بانتظار القرار', $stats['requests_pending'], $stats['requests_accepted'].' مقبولًا حتى الآن'],
            ['بلاغات مفتوحة', $stats['reports_open'], 'تحتاج مراجعة'],
            ['أحداث منتهية', $stats['events_past'], $stats['events_cancelled'].' ملغاة'],
            ['التقييمات', $stats['ratings'], $stats['comments'].' تعليقًا'],
            ['حسابات غير موثّقة', $stats['unverified'], 'لم تفعّل بريدها'],
            ['حسابات موقوفة', $stats['suspended'], 'ممنوعة من الدخول'],
        ];
    @endphp
    <div class="row g-3 mb-4">
        @foreach ($tiles as [$label, $num, $sub])
            <div class="col-6 col-xl-3">
                <div class="stat-tile h-100">
                    <div class="stat-tile__num">{{ $num }}</div>
                    <div class="stat-tile__label fw-bold text-white">{{ $label }}</div>
                    <div class="stat-tile__label">{{ $sub }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="liqa-card p-3 p-md-4">
                <h2 class="h5 liqa-title mb-1">النشاط خلال آخر ٣٠ يومًا</h2>
                <p class="small text-secondary mb-3">التسجيلات الجديدة والأحداث المنشأة يوميًا.</p>
                @include('admin.partials.line-chart', [
                    'title' => 'التسجيلات والأحداث خلال ٣٠ يومًا',
                    'labels' => $chart['labels'],
                    'series' => [
                        ['name' => 'تسجيلات جديدة', 'color' => 'var(--series-1)', 'data' => $chart['signups']],
                        ['name' => 'أحداث جديدة', 'color' => 'var(--series-2)', 'data' => $chart['events']],
                    ],
                ])
            </div>
        </div>
        <div class="col-lg-6">
            <div class="liqa-card p-3 p-md-4 h-100">
                <h2 class="h5 liqa-title mb-3">الأحداث حسب المجال</h2>
                @include('admin.partials.bars', ['rows' => $types, 'color' => 'var(--series-1)'])
            </div>
        </div>
        <div class="col-lg-6">
            <div class="liqa-card p-3 p-md-4 h-100">
                <h2 class="h5 liqa-title mb-3">الأحداث حسب المكان</h2>
                @include('admin.partials.bars', ['rows' => $places, 'color' => 'var(--series-3)'])
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="liqa-card p-3 p-md-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 liqa-title mb-0">آخر الإجراءات</h2>
                    <a href="{{ route('admin.logs') }}" class="small">سجل الإجراءات</a>
                </div>
                @forelse ($recentLogs as $log)
                    <div class="request-row mb-2 d-block">
                        <span class="liqa-badge {{ $log->actionClass() }}">{{ $log->actionLabel() }}</span>
                        <span class="small text-secondary ms-2">{{ $log->admin_name ?: '—' }} · {{ $log->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-secondary mb-0">لا توجد إجراءات مسجّلة بعد.</p>
                @endforelse
            </div>
        </div>
        <div class="col-xl-6">
            <div class="liqa-card p-3 p-md-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 liqa-title mb-0">بلاغات مفتوحة</h2>
                    <a href="{{ route('admin.reports') }}" class="small">عرض الكل</a>
                </div>
                @forelse ($openReports as $report)
                    <a href="{{ route('admin.reports') }}" class="request-row mb-2 text-decoration-none d-block">
                        <span class="liqa-badge liqa-badge--pink">{{ \App\Models\Report::REASONS[$report->reason] ?? $report->reason }}</span>
                        <span class="small text-secondary ms-2">{{ $report->target_type === 'user' ? 'مستخدم' : 'حدث' }} · {{ $report->created_at->diffForHumans() }}</span>
                    </a>
                @empty
                    <p class="text-secondary mb-0">لا توجد بلاغات مفتوحة.</p>
                @endforelse
            </div>
        </div>

        <div class="col-xl-6">
            <div class="liqa-card p-3 p-md-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 liqa-title mb-0">أحداث تبدأ قريبًا</h2>
                    <a href="{{ route('admin.events') }}" class="small">عرض الكل</a>
                </div>
                @forelse ($soonEvents as $event)
                    <a href="{{ route('events.show', $event) }}" class="request-row mb-2 text-decoration-none d-block">
                        <span class="d-block fw-bold text-white">{{ $event->title }}</span>
                        <span class="d-block small text-secondary">{{ $event->whenLabel() }} · {{ $event->host->name }} · {{ $event->accepted_requests_count }}/{{ $event->capacity }}</span>
                    </a>
                @empty
                    <p class="text-secondary mb-0">لا توجد أحداث قادمة.</p>
                @endforelse
            </div>
        </div>

        <div class="col-12">
            <div class="liqa-card p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 liqa-title mb-0">أحدث المسجّلين</h2>
                    <a href="{{ route('admin.users') }}" class="small">عرض الكل</a>
                </div>
                <div class="row g-2">
                    @foreach ($recentUsers as $u)
                        <div class="col-md-6 col-xl-4">
                            <a href="{{ route('admin.users.show', $u) }}" class="request-row text-decoration-none">
                                <x-avatar :user="$u" :size="36" />
                                <span>
                                    <span class="d-block fw-bold text-white">{{ $u->name }}</span>
                                    <span class="d-block small text-secondary">{{ $u->created_at->diffForHumans() }}</span>
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
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
setTimeout(function () { location.reload(); }, 60000);</script>
@endpush
