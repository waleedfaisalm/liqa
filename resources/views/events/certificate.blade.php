<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('شهادة حضور') }} — {{ $event->title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e9e5f5; font-family: 'Cairo', system-ui, sans-serif; color: #1a1033; }
        .bar { display: flex; gap: .75rem; justify-content: center; padding: 1rem; }
        .bar a, .bar button { font: inherit; font-weight: 700; padding: .65rem 1.6rem; border-radius: 999px; border: 0; cursor: pointer; text-decoration: none; color: #fff; background: linear-gradient(135deg, #9333ea, #db2777); }
        .bar a { background: #fff; color: #5b3fa0; border: 1px solid #d6cdf0; }
        .sheet { width: 297mm; height: 210mm; margin: 0 auto 2rem; background: #fff; position: relative; padding: 14mm; box-shadow: 0 10px 40px rgba(60, 30, 120, .18); }
        .frame { height: 100%; border: 3px solid #9333ea; border-radius: 6mm; padding: 10mm 16mm; display: flex; flex-direction: column; align-items: center; justify-content: space-between; text-align: center; position: relative; background: radial-gradient(circle at 50% -20%, #f4ecff, #fff 55%); }
        .frame::after { content: ''; position: absolute; inset: 3mm; border: 1px solid #d9c2f5; border-radius: 4mm; pointer-events: none; }
        .brand { display: flex; align-items: center; gap: .6rem; font-weight: 800; font-size: 1.6rem; }
        .brand svg { width: 46px; height: 46px; }
        h1 { margin: 0; font-size: 2.6rem; font-weight: 800; color: #6d28d9; }
        .lead { font-size: 1.15rem; color: #5b4d86; margin: 0; }
        .name { font-size: 2.6rem; font-weight: 800; margin: .2rem 0; padding: 0 2rem .3rem; border-bottom: 2px solid #db2777; }
        .event { font-size: 1.7rem; font-weight: 700; margin: .2rem 0; }
        .meta { font-size: 1.05rem; color: #5b4d86; margin: 0; }
        .foot { width: 100%; display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; font-size: .95rem; color: #5b4d86; }
        .code { font-family: 'Courier New', monospace; font-weight: 700; font-size: 1.15rem; color: #1a1033; letter-spacing: 1px; direction: ltr; unicode-bidi: isolate; }
        .foot .end { text-align: end; }
        @media print { body { background: #fff; } .bar { display: none; } .sheet { margin: 0; box-shadow: none; } }
        @media (max-width: 1100px) and (not print) { .sheet { zoom: .5; } }
    </style>
</head>
<body>
    <div class="bar">
        <button type="button" onclick="window.print()">{{ __('طباعة / حفظ PDF') }}</button>
        <a href="{{ route('events.show', $event) }}">{{ __('← العودة إلى الحدث') }}</a>
    </div>

    <div class="sheet">
        <div class="frame">
            <div class="brand"><x-logo :size="46" /> <span>{{ __(\App\Models\Setting::site('name')) }}</span></div>

            <div>
                <h1>{{ __('شهادة حضور') }}</h1>
                <p class="lead">{{ __('تشهد منصة لقاء بأن') }}</p>
                <div class="name">{{ $user->name }}</div>
                <p class="lead">{{ __('قد حضر فعاليّة') }}</p>
                <div class="event">«{{ $event->title }}»</div>
                <p class="meta">
                    {{ $event->starts_at->translatedFormat('l j F Y') }} · {{ $event->durationLabel() }}
                    · {{ __('بتنظيم :n', ['n' => $event->host->name]) }}
                </p>
            </div>

            <div class="foot">
                <div>
                    <div>{{ __('رمز التحقق') }}</div>
                    <div class="code">{{ $code }}</div>
                </div>
                <div class="end">
                    <div>{{ __('للتحقق من صحة الشهادة:') }}</div>
                    <div class="code" style="font-size: .85rem">{{ $verifyUrl }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
