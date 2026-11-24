{{-- صحة المجتمع + مراحل حياة العضو + الأفواج --}}
<h2 class="h5 liqa-title mb-3">صحة المجتمع</h2>
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        @php
            $score = $health['score'];
            $tone = $score === null ? 'text-secondary' : ($score >= 80 ? 'text-success' : ($score >= 60 ? 'text-info' : ($score >= 40 ? 'text-warning' : 'text-danger')));
        @endphp
        <div class="liqa-card p-3 p-md-4 h-100 text-center">
            <div class="small text-secondary mb-1">مؤشر صحة المجتمع</div>
            <div class="display-3 fw-bold {{ $tone }}">{{ $score ?? '—' }}<span class="fs-5 text-secondary">/100</span></div>
            <div class="fw-bold {{ $tone }}">{{ $health['label'] }}</div>
            @if ($healthDelta !== null)
                <div class="small mt-1 {{ $healthDelta >= 0 ? 'text-success' : 'text-danger' }}">{{ $healthDelta >= 0 ? '▲' : '▼' }} {{ abs($healthDelta) }} نقطة عن الأسبوع الماضي</div>
            @else
                <div class="small mt-1 text-secondary">يظهر الاتجاه بعد أسبوع من تراكم اللقطات.</div>
            @endif
            @if ($health['missing'])
                <p class="small text-secondary mt-3 mb-0">بعض المكونات غير محسوبة لقلة البيانات، فيُعاد توزيع الأوزان على المتاح.</p>
            @endif
        </div>
    </div>
    <div class="col-lg-8">
        <div class="liqa-card p-3 p-md-4 h-100">
            <div class="fw-bold text-white mb-3">مكوّنات المؤشر</div>
            @forelse ($health['parts'] as $p)
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-white">{{ $p['label'] }} <span class="text-secondary">(وزن {{ $p['weight'] }})</span></span>
                        <span class="text-secondary">{{ $p['percent'] }}٪ · {{ $p['text'] }}</span>
                    </div>
                    <div class="seat-meter"><span style="width: {{ $p['percent'] }}%"></span></div>
                </div>
            @empty
                <p class="text-secondary mb-0">لا توجد بيانات كافية بعد.</p>
            @endforelse
        </div>
    </div>
</div>

<h2 class="h5 liqa-title mb-1">مراحل حياة الأعضاء</h2>
<p class="small text-secondary mb-3">جديد: انضم خلال 14 يومًا · نشط: ظهر خلال 30 يومًا · عائد: حضر بعد انقطاع 60 يومًا أو أكثر · مهدّد: آخر ظهور بين 30 و90 يومًا · غير نشط: أكثر من 90 يومًا.</p>
<div class="row g-3 mb-4">
    @foreach (\App\Support\CommunityMetrics::STAGES as $k => $label)
        <div class="col-6 col-lg">
            <div class="stat-tile h-100">
                <div class="stat-tile__num">{{ $lifecycle[$k] }}</div>
                <div class="stat-tile__label">{{ $label }}</div>
            </div>
        </div>
    @endforeach
</div>

<h2 class="h5 liqa-title mb-1">الأفواج الشهرية</h2>
<p class="small text-secondary mb-3">أعضاء كل شهر تسجيل: كم منهم حضر لقاءً، وكم عاد لثانٍ، وكم لا يزال نشطًا. النسب الأعلى في الأشهر الأحدث تعني التزامًا أفضل.</p>
<div class="liqa-card p-3 mb-4">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>شهر التسجيل</th><th>الأعضاء</th><th>حضروا لقاءً</th><th>حضروا أكثر من لقاء</th><th>نشطون آخر 30 يومًا</th></tr></thead>
            <tbody>
                @foreach ($cohorts as $c)
                    <tr>
                        <td>{{ $c['month'] }}</td>
                        <td>{{ $c['size'] }}</td>
                        <td>{{ $c['attended'] !== null ? $c['attended'].'٪' : '—' }}</td>
                        <td>{{ $c['returned'] !== null ? $c['returned'].'٪' : '—' }}</td>
                        <td>{{ $c['active'] !== null ? $c['active'].'٪' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
