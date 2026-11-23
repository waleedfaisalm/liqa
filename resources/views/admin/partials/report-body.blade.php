{{-- جسم التقرير المشترك: مؤشرات الفترة + الحضور الفعلي + الاحتفاظ + الأفضل --}}
<div class="row g-3 mb-4">
    @foreach ($r['kpis'] as $k)
        <div class="col-6 col-lg-3">
            <div class="stat-tile h-100">
                <div class="stat-tile__num">{{ $k['value'] }}</div>
                <div class="stat-tile__label">{{ $k['label'] }}</div>
                <div class="small mt-1 {{ $k['delta'] === null ? 'text-secondary' : ($k['delta'] >= 0 ? 'text-success' : 'text-danger') }}">
                    @if ($k['delta'] === null) {{ $k['value'] > 0 ? 'جديد' : '—' }}
                    @else {{ $k['delta'] >= 0 ? '▲' : '▼' }} {{ abs($k['delta']) }}٪ عن الفترة السابقة @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="stat-tile h-100"><div class="stat-tile__num">{{ $r['attendanceRate'] !== null ? $r['attendanceRate'].'٪' : '—' }}</div><div class="stat-tile__label">نسبة الحضور الفعلي من المقبولين</div><div class="small text-secondary">{{ $r['endedEvents'] }} حدثًا منتهيًا</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-tile h-100"><div class="stat-tile__num">{{ $r['avgMinutes'] !== null ? $r['avgMinutes'] : '—' }}</div><div class="stat-tile__label">متوسط دقائق البقاء في الغرفة</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-tile h-100"><div class="stat-tile__num">{{ $r['retention']['returningRate'] !== null ? $r['retention']['returningRate'].'٪' : '—' }}</div><div class="stat-tile__label">حضروا أكثر من لقاء</div><div class="small text-secondary">{{ $r['retention']['returning'] }} من {{ $r['retention']['attenders'] }} حاضرًا</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-tile h-100"><div class="stat-tile__num">{{ $r['retention']['active30Rate'] !== null ? $r['retention']['active30Rate'].'٪' : '—' }}</div><div class="stat-tile__label">نشطون آخر 30 يومًا</div><div class="small text-secondary">{{ $r['retention']['active30'] }} من {{ $r['retention']['members'] }} عضوًا</div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="liqa-card p-3 h-100">
            <div class="fw-bold text-white mb-2">أفضل الأحداث حضورًا</div>
            @if (! $r['topEvents'])
                <p class="small text-secondary mb-0">لا توجد أحداث منتهية في هذه الفترة.</p>
            @else
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>الحدث</th><th>مقبولون</th><th>حضروا</th><th>التقييم</th></tr></thead>
                    <tbody>
                        @foreach ($r['topEvents'] as $e)
                            <tr><td>{{ $e['title'] }}<div class="small text-secondary">{{ $e['date'] }}</div></td><td>{{ $e['accepted'] }}</td><td>{{ $e['attended'] }}</td><td>{{ $e['rating'] !== null ? $e['rating'].' ★' : '—' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="liqa-card p-3 h-100">
            <div class="fw-bold text-white mb-2">أكثر المجالات</div>
            @forelse ($r['types'] as $t)
                <div class="d-flex justify-content-between small mb-1"><span>{{ $t['label'] }}</span><span class="text-secondary">{{ $t['value'] }}</span></div>
            @empty <p class="small text-secondary mb-0">—</p> @endforelse
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="liqa-card p-3 h-100">
            <div class="fw-bold text-white mb-2">أكثر المدن</div>
            @forelse ($r['cities'] as $t)
                <div class="d-flex justify-content-between small mb-1"><span>{{ $t['label'] }}</span><span class="text-secondary">{{ $t['value'] }}</span></div>
            @empty <p class="small text-secondary mb-0">—</p> @endforelse
        </div>
    </div>
</div>
