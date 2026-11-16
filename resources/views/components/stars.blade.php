@props(['value' => 0, 'size' => 16])
<span class="liqa-stars" role="img" aria-label="{{ __(':v من ٥ نجوم', ['v' => $value]) }}" style="font-size: {{ $size }}px">
    @for ($i = 1; $i <= 5; $i++)
        <span class="{{ $i <= round($value) ? 'is-on' : '' }}">★</span>
    @endfor
</span>
