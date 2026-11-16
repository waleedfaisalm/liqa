{{-- رسم توضيحي أصلي: مستقلون حول طاولة لقاء --}}
@php $u = 'ha'.substr(md5(uniqid('', true)), 0, 6); @endphp
<svg viewBox="0 0 520 440" class="hero-art__svg" role="img" aria-label="{{ __('رسم توضيحي لمستقلين يلتقون حول طاولة') }}" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <radialGradient id="{{ $u }}glow" cx="50%" cy="50%" r="50%">
            <stop offset="0" stop-color="#9333ea" stop-opacity=".38"/>
            <stop offset=".55" stop-color="#db2777" stop-opacity=".12"/>
            <stop offset="1" stop-color="#0b0a14" stop-opacity="0"/>
        </radialGradient>
        <linearGradient id="{{ $u }}table" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#3a2a73"/>
            <stop offset="1" stop-color="#1b1638"/>
        </linearGradient>
        <linearGradient id="{{ $u }}p1" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#a855f7"/><stop offset="1" stop-color="#6366f1"/></linearGradient>
        <linearGradient id="{{ $u }}p2" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fb923c"/><stop offset="1" stop-color="#db2777"/></linearGradient>
        <linearGradient id="{{ $u }}p3" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#38bdf8"/><stop offset="1" stop-color="#6366f1"/></linearGradient>
        <linearGradient id="{{ $u }}p4" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#34d399"/><stop offset="1" stop-color="#0ea5e9"/></linearGradient>
        <linearGradient id="{{ $u }}p5" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f472b6"/><stop offset="1" stop-color="#9333ea"/></linearGradient>
    </defs>

    <ellipse cx="260" cy="225" rx="250" ry="205" fill="url(#{{ $u }}glow)"/>

    {{-- خطوط الربط --}}
    <g fill="none" stroke="#a78bfa" stroke-opacity=".45" stroke-width="1.5" stroke-dasharray="3 7" stroke-linecap="round">
        <path d="M118 168 Q190 95 260 118"/>
        <path d="M260 118 Q330 95 402 168"/>
        <path d="M118 168 Q96 250 138 318"/>
        <path d="M402 168 Q424 250 382 318"/>
    </g>

    {{-- الأشخاص خلف الطاولة --}}
    <g>
        <path d="M74 232 a44 44 0 0 1 88 0 v22 h-88z" fill="url(#{{ $u }}p3)" opacity=".92"/>
        <circle cx="118" cy="176" r="26" fill="url(#{{ $u }}p3)"/>
        <path d="M94 170 a26 26 0 0 1 48 -6 q-24 -8 -48 6z" fill="#fff" fill-opacity=".28"/>
    </g>
    <g>
        <path d="M216 190 a44 44 0 0 1 88 0 v26 h-88z" fill="url(#{{ $u }}p1)" opacity=".95"/>
        <circle cx="260" cy="132" r="28" fill="url(#{{ $u }}p1)"/>
        <path d="M234 126 a28 28 0 0 1 52 -6 q-26 -9 -52 6z" fill="#fff" fill-opacity=".28"/>
    </g>
    <g>
        <path d="M358 232 a44 44 0 0 1 88 0 v22 h-88z" fill="url(#{{ $u }}p2)" opacity=".92"/>
        <circle cx="402" cy="176" r="26" fill="url(#{{ $u }}p2)"/>
        <path d="M378 170 a26 26 0 0 1 48 -6 q-24 -8 -48 6z" fill="#fff" fill-opacity=".28"/>
    </g>

    {{-- الطاولة --}}
    <ellipse cx="260" cy="292" rx="172" ry="64" fill="#0b0a14" opacity=".5"/>
    <ellipse cx="260" cy="272" rx="172" ry="62" fill="url(#{{ $u }}table)" stroke="#5b3aa8" stroke-opacity=".7"/>
    <ellipse cx="260" cy="266" rx="150" ry="50" fill="none" stroke="#fff" stroke-opacity=".07"/>

    {{-- فناجين قهوة --}}
    <g>
        <ellipse cx="205" cy="272" rx="20" ry="7" fill="#2b2250"/>
        <path d="M192 252 h26 v13 a13 9 0 0 1 -26 0z" fill="#f1c9ee"/>
        <path d="M218 256 q9 1 8 8 q-1 6 -8 5" fill="none" stroke="#f1c9ee" stroke-width="3"/>
        <path d="M200 244 q-4 -6 0 -12 M208 244 q-4 -6 0 -12" fill="none" stroke="#c4b5fd" stroke-opacity=".7" stroke-width="2" stroke-linecap="round"/>
    </g>
    <g>
        <ellipse cx="318" cy="276" rx="20" ry="7" fill="#2b2250"/>
        <path d="M305 256 h26 v13 a13 9 0 0 1 -26 0z" fill="#fbcfe8"/>
        <path d="M331 260 q9 1 8 8 q-1 6 -8 5" fill="none" stroke="#fbcfe8" stroke-width="3"/>
        <path d="M313 248 q-4 -6 0 -12 M321 248 q-4 -6 0 -12" fill="none" stroke="#c4b5fd" stroke-opacity=".7" stroke-width="2" stroke-linecap="round"/>
    </g>
    {{-- حاسوب محمول --}}
    <g>
        <path d="M236 252 h48 l6 -22 h-60z" fill="#1d1a3a" stroke="#7c3aed" stroke-opacity=".8"/>
        <path d="M232 256 h56 l4 6 h-64z" fill="#4c3a8c"/>
        <circle cx="260" cy="241" r="4" fill="#f472b6"/>
    </g>

    {{-- الأشخاص أمام الطاولة --}}
    <g>
        <path d="M100 390 a46 46 0 0 1 92 0 v24 h-92z" fill="url(#{{ $u }}p4)" opacity=".95"/>
        <circle cx="146" cy="330" r="27" fill="url(#{{ $u }}p4)"/>
        <path d="M121 324 a27 27 0 0 1 50 -6 q-25 -9 -50 6z" fill="#fff" fill-opacity=".28"/>
    </g>
    <g>
        <path d="M328 390 a46 46 0 0 1 92 0 v24 h-92z" fill="url(#{{ $u }}p5)" opacity=".95"/>
        <circle cx="374" cy="330" r="27" fill="url(#{{ $u }}p5)"/>
        <path d="M349 324 a27 27 0 0 1 50 -6 q-25 -9 -50 6z" fill="#fff" fill-opacity=".28"/>
    </g>

    {{-- فقاعات حوار وأيقونات --}}
    <g>
        <rect x="150" y="64" width="62" height="38" rx="12" fill="#1d1a3a" stroke="#7c3aed"/>
        <path d="M168 102 l-8 12 l20 -12z" fill="#1d1a3a" stroke="#7c3aed" stroke-linejoin="round"/>
        <circle cx="168" cy="83" r="3.6" fill="#a78bfa"/><circle cx="181" cy="83" r="3.6" fill="#f472b6"/><circle cx="194" cy="83" r="3.6" fill="#38bdf8"/>
    </g>
    <g>
        <rect x="318" y="48" width="66" height="38" rx="12" fill="#1d1a3a" stroke="#db2777"/>
        <path d="M366 86 l10 12 l-22 -12z" fill="#1d1a3a" stroke="#db2777" stroke-linejoin="round"/>
        <path d="M334 67 h34 M334 76 h22" stroke="#f9a8d4" stroke-width="3" stroke-linecap="round"/>
    </g>
    <g>
        <circle cx="448" cy="102" r="20" fill="#1d1a3a" stroke="#38bdf8"/>
        <path d="M448 92 a8 8 0 0 1 8 8 c0 6 -8 14 -8 14 s-8 -8 -8 -14 a8 8 0 0 1 8 -8z" fill="none" stroke="#7dd3fc" stroke-width="2.4"/>
        <circle cx="448" cy="100" r="2.8" fill="#7dd3fc"/>
    </g>
    <g>
        <circle cx="70" cy="110" r="19" fill="#1d1a3a" stroke="#fbbf24"/>
        <path d="M70 99 l3.4 7 7.6 1.1 -5.5 5.4 1.3 7.6 -6.8 -3.6 -6.8 3.6 1.3 -7.6 -5.5 -5.4 7.6 -1.1z" fill="#fbbf24"/>
    </g>

    {{-- نجوم زخرفية --}}
    <g fill="#c4b5fd" fill-opacity=".7">
        <circle cx="44" cy="260" r="2.4"/><circle cx="486" cy="250" r="2.8"/><circle cx="236" cy="30" r="2.4"/><circle cx="470" cy="330" r="2"/><circle cx="52" cy="340" r="2"/>
    </g>
</svg>
