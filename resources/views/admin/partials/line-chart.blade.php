@php
    $W = 640; $H = 220; $L = 34; $R = 14; $T = 14; $B = 26;
    $n = count($labels);
    $max = max(1, max(array_merge(...array_column($series, 'data'))));
    $step = $max <= 4 ? 1 : (int) ceil($max / 4);
    $top = $step * 4 > $max ? $step * 4 : $max;
    if ($max <= 4) { $top = 4; $step = 1; }
    $x = fn ($i) => $L + ($n > 1 ? $i * ($W - $L - $R) / ($n - 1) : 0);
    $y = fn ($v) => $T + ($H - $T - $B) * (1 - $v / $top);
@endphp
<div class="lchart" data-chart>
    <div class="lchart__legend">
        @foreach ($series as $s)
            <span class="lchart__key"><i style="background: {{ $s['color'] }}"></i>{{ $s['name'] }} <b>{{ array_sum($s['data']) }}</b></span>
        @endforeach
    </div>
    <div class="lchart__wrap" dir="ltr">
        <svg viewBox="0 0 {{ $W }} {{ $H }}" class="lchart__svg" role="img" aria-label="{{ $title }}">
            @for ($g = 0; $g <= 4; $g++)
                @php $gv = $g * $top / 4; @endphp
                <line x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $y($gv) }}" y2="{{ $y($gv) }}" class="lchart__grid"/>
                <text x="{{ $L - 6 }}" y="{{ $y($gv) + 4 }}" text-anchor="end" class="lchart__tick">{{ round($gv) }}</text>
            @endfor
            @foreach (array_unique([0, intdiv($n - 1, 4), intdiv(2 * ($n - 1), 4), intdiv(3 * ($n - 1), 4), $n - 1]) as $i)
                @if ($i < $n)
                    <text x="{{ $x($i) }}" y="{{ $H - 6 }}" text-anchor="middle" class="lchart__tick">{{ $labels[$i] }}</text>
                @endif
            @endforeach
            @foreach ($series as $s)
                <polyline fill="none" stroke="{{ $s['color'] }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"
                    points="{{ collect($s['data'])->map(fn ($v, $i) => round($x($i), 1).','.round($y($v), 1))->implode(' ') }}"/>
                <circle cx="{{ $x($n - 1) }}" cy="{{ $y(end($s['data'])) }}" r="4.5" fill="{{ $s['color'] }}"/>
            @endforeach
            <line class="lchart__cross" x1="0" x2="0" y1="{{ $T }}" y2="{{ $H - $B }}" style="display:none"/>
            <rect class="lchart__hit" x="{{ $L }}" y="{{ $T }}" width="{{ $W - $L - $R }}" height="{{ $H - $T - $B }}" fill="transparent"
                  data-l="{{ $L }}" data-w="{{ $W - $L - $R }}" data-n="{{ $n }}"/>
        </svg>
        <div class="lchart__tip" style="display:none"></div>
    </div>
    <details class="lchart__table">
        <summary>عرض البيانات كجدول</summary>
        <div class="table-responsive" style="max-height:220px">
            <table class="admin-table">
                <thead><tr><th>{{ $unit ?? 'اليوم' }}</th>@foreach ($series as $s)<th>{{ $s['name'] }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach (array_reverse(array_keys($labels)) as $i)
                        <tr><td>{{ $labels[$i] }}</td>@foreach ($series as $s)<td>{{ $s['data'][$i] }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
    <script type="application/json" class="lchart__json">{!! json_encode(['labels' => $labels, 'series' => array_map(fn ($s) => ['name' => $s['name'], 'color' => $s['color'], 'data' => $s['data']], $series)], JSON_UNESCAPED_UNICODE) !!}</script>
</div>
