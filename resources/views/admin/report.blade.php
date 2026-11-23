<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>تقرير لقاء — آخر {{ $range }} يومًا</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Cairo', system-ui, sans-serif; color: #1a1033; background: #eeeaf8; }
        .bar { display: flex; gap: .6rem; justify-content: center; align-items: center; padding: 1rem; flex-wrap: wrap; }
        .bar a, .bar button { font: inherit; font-weight: 700; padding: .55rem 1.3rem; border-radius: 999px; border: 1px solid #d6cdf0; background: #fff; color: #5b3fa0; text-decoration: none; cursor: pointer; }
        .bar a.on { background: #6d28d9; color: #fff; border-color: #6d28d9; }
        .bar button { background: linear-gradient(135deg, #9333ea, #db2777); color: #fff; border: 0; }
        .page { max-width: 210mm; margin: 0 auto 2rem; background: #fff; padding: 14mm; box-shadow: 0 10px 40px rgba(60, 30, 120, .15); }
        h1 { margin: 0; font-size: 1.7rem; color: #6d28d9; }
        .sub { color: #6b5d9a; margin: .2rem 0 1.2rem; }
        h2 { font-size: 1.05rem; margin: 1.4rem 0 .5rem; border-inline-start: 4px solid #db2777; padding-inline-start: .6rem; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        th, td { padding: .45rem .5rem; border-bottom: 1px solid #e6e0f5; text-align: start; }
        th { background: #f5f1ff; font-weight: 700; }
        .up { color: #15803d; } .down { color: #b91c1c; } .mut { color: #6b5d9a; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .6rem; }
        .tile { border: 1px solid #e6e0f5; border-radius: 10px; padding: .6rem .7rem; }
        .tile b { display: block; font-size: 1.4rem; }
        .tile span { font-size: .8rem; color: #6b5d9a; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media print { body { background: #fff; } .bar { display: none; } .page { box-shadow: none; padding: 0; max-width: none; margin: 0; } }
        @media (max-width: 640px) { .grid { grid-template-columns: repeat(2, 1fr); } .two { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="bar">
        @foreach ([7, 30, 90] as $d)
            <a href="{{ route('admin.report', ['range' => $d]) }}" class="{{ $range === $d ? 'on' : '' }}">{{ $d }} يومًا</a>
        @endforeach
        <button type="button" onclick="window.print()">طباعة / حفظ PDF</button>
        <a href="{{ route('admin.stats') }}">← الإحصاءات</a>
    </div>

    <div class="page">
        <h1>تقرير منصة لقاء</h1>
        <p class="sub">من {{ $r['from']->format('Y-m-d') }} إلى {{ $r['to']->format('Y-m-d') }} ({{ $range }} يومًا) · أُنشئ {{ now()->format('Y-m-d H:i') }}</p>

        <h2>مؤشرات الفترة</h2>
        <table>
            <thead><tr><th>المؤشر</th><th>الفترة</th><th>السابقة</th><th>التغيّر</th></tr></thead>
            <tbody>
                @foreach ($r['kpis'] as $k)
                    <tr>
                        <td>{{ $k['label'] }}</td><td><b>{{ $k['value'] }}</b></td><td class="mut">{{ $k['prev'] }}</td>
                        <td class="{{ $k['delta'] === null ? 'mut' : ($k['delta'] >= 0 ? 'up' : 'down') }}">
                            {{ $k['delta'] === null ? '—' : ($k['delta'] >= 0 ? '▲ ' : '▼ ').abs($k['delta']).'٪' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2>الحضور الفعلي والاحتفاظ</h2>
        <div class="grid">
            <div class="tile"><b>{{ $r['attendanceRate'] !== null ? $r['attendanceRate'].'٪' : '—' }}</b><span>الحضور الفعلي من المقبولين ({{ $r['endedEvents'] }} حدثًا)</span></div>
            <div class="tile"><b>{{ $r['avgMinutes'] ?? '—' }}</b><span>متوسط دقائق البقاء</span></div>
            <div class="tile"><b>{{ $r['retention']['returningRate'] !== null ? $r['retention']['returningRate'].'٪' : '—' }}</b><span>حضروا أكثر من لقاء ({{ $r['retention']['returning'] }}/{{ $r['retention']['attenders'] }})</span></div>
            <div class="tile"><b>{{ $r['retention']['active30Rate'] !== null ? $r['retention']['active30Rate'].'٪' : '—' }}</b><span>نشطون آخر 30 يومًا ({{ $r['retention']['active30'] }}/{{ $r['retention']['members'] }})</span></div>
        </div>

        <h2>أفضل الأحداث حضورًا</h2>
        @if ($r['topEvents'])
            <table>
                <thead><tr><th>الحدث</th><th>التاريخ</th><th>مقبولون</th><th>حضروا</th><th>التقييم</th></tr></thead>
                <tbody>
                    @foreach ($r['topEvents'] as $e)
                        <tr><td>{{ $e['title'] }}</td><td>{{ $e['date'] }}</td><td>{{ $e['accepted'] }}</td><td>{{ $e['attended'] }}</td><td>{{ $e['rating'] !== null ? $e['rating'].' ★' : '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="mut">لا توجد أحداث منتهية في هذه الفترة.</p>
        @endif

        <div class="two">
            <div>
                <h2>أكثر المجالات نشاطًا</h2>
                <table><tbody>@forelse ($r['types'] as $t)<tr><td>{{ $t['label'] }}</td><td>{{ $t['value'] }}</td></tr>@empty<tr><td class="mut">—</td></tr>@endforelse</tbody></table>
            </div>
            <div>
                <h2>أكثر المدن نشاطًا</h2>
                <table><tbody>@forelse ($r['cities'] as $t)<tr><td>{{ $t['label'] }}</td><td>{{ $t['value'] }}</td></tr>@empty<tr><td class="mut">—</td></tr>@endforelse</tbody></table>
            </div>
        </div>
    </div>
</body>
</html>
