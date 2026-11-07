@props(['user', 'size' => 40])

@if ($user->avatarUrl())
    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="liqa-avatar liqa-avatar--img"
         style="width: {{ $size }}px; height: {{ $size }}px">
@else
    <span class="liqa-avatar" style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.4) }}px"
          aria-hidden="true">{{ $user->initial() }}</span>
@endif
