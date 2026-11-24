<!DOCTYPE html>
<html lang="ar" dir="rtl">
<body style="margin:0;background:#f3f0fb;font-family:Tahoma,Arial,sans-serif;color:#1a1033">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px 12px">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden">
    <tr><td style="background:#6d28d9;color:#fff;padding:22px 26px">
        <div style="font-size:22px;font-weight:bold">تقرير لقاء الأسبوعي</div>
        <div style="font-size:13px;opacity:.85;margin-top:4px">{{ $r['from']->format('Y-m-d') }} — {{ $r['to']->format('Y-m-d') }}</div>
    </td></tr>
    <tr><td style="padding:22px 26px">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px">
            @foreach ($r['kpis'] as $k)
                <tr>
                    <td style="padding:8px 0;border-bottom:1px solid #eee">{{ $k['label'] }}</td>
                    <td style="padding:8px 0;border-bottom:1px solid #eee;font-weight:bold" align="center">{{ $k['value'] }}</td>
                    <td style="padding:8px 0;border-bottom:1px solid #eee;font-size:12px;color:{{ $k['delta'] === null ? '#6b5d9a' : ($k['delta'] >= 0 ? '#15803d' : '#b91c1c') }}" align="left">
                        {{ $k['delta'] === null ? '—' : ($k['delta'] >= 0 ? '▲ ' : '▼ ').abs($k['delta']).'٪' }}
                    </td>
                </tr>
            @endforeach
        </table>

        <div style="font-weight:bold;margin:20px 0 8px">الحضور والاحتفاظ</div>
        <div style="font-size:14px;line-height:1.9">
            • نسبة الحضور الفعلي من المقبولين: <b>{{ $r['attendanceRate'] !== null ? $r['attendanceRate'].'٪' : '—' }}</b> ({{ $r['endedEvents'] }} حدثًا منتهيًا)<br>
            • متوسط البقاء في الغرفة: <b>{{ $r['avgMinutes'] ?? '—' }}</b> دقيقة<br>
            • حضروا أكثر من لقاء: <b>{{ $r['retention']['returningRate'] !== null ? $r['retention']['returningRate'].'٪' : '—' }}</b><br>
            • نشطون آخر 30 يومًا: <b>{{ $r['retention']['active30Rate'] !== null ? $r['retention']['active30Rate'].'٪' : '—' }}</b> ({{ $r['retention']['active30'] }} من {{ $r['retention']['members'] }})
        </div>

        <div style="font-weight:bold;margin:20px 0 8px">صحة المجتمع</div>
        <div style="font-size:14px;line-height:1.9">
            • المؤشر: <b>{{ $r['health']['score'] ?? '—' }}</b> / 100 ({{ $r['health']['label'] }})<br>
            • جدد: <b>{{ $r['lifecycle']['new'] }}</b> · نشطون: <b>{{ $r['lifecycle']['active'] }}</b> · عائدون: <b>{{ $r['lifecycle']['returning'] }}</b><br>
            • مهدّدون بالمغادرة: <b>{{ $r['lifecycle']['at_risk'] }}</b> · غير نشطين: <b>{{ $r['lifecycle']['inactive'] }}</b>
        </div>

        @if ($r['topEvents'])
            <div style="font-weight:bold;margin:20px 0 8px">أفضل الأحداث حضورًا</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px">
                @foreach ($r['topEvents'] as $e)
                    <tr><td style="padding:6px 0;border-bottom:1px solid #eee">{{ $e['title'] }}</td><td style="padding:6px 0;border-bottom:1px solid #eee" align="left">{{ $e['attended'] }} / {{ $e['accepted'] }}</td></tr>
                @endforeach
            </table>
        @endif

        <div style="margin-top:24px;text-align:center">
            <a href="{{ url('/admin/report?range=7') }}" style="background:#6d28d9;color:#fff;text-decoration:none;padding:11px 26px;border-radius:999px;font-weight:bold;font-size:14px">فتح التقرير الكامل</a>
        </div>
    </td></tr>
</table>
</td></tr></table>
</body>
</html>
