@php $max = max(1, max(array_column($rows, 'value') ?: [0])); @endphp
@if (array_sum(array_column($rows, 'value')) === 0)
    <p class="text-secondary mb-0">لا توجد بيانات كافية بعد.</p>
@else
    <div class="hbars">
        @foreach ($rows as $row)
            <div class="hbars__row">
                <span class="hbars__label">{{ $row['label'] }}</span>
                <span class="hbars__track"><span class="hbars__fill" style="width: {{ round($row['value'] / $max * 100) }}%; background: {{ $color }}"></span></span>
                <span class="hbars__val">{{ $row['value'] }}</span>
            </div>
        @endforeach
    </div>
@endif
