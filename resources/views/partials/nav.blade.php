@php
    $items = [
        ['route' => 'home', 'label' => 'الرئيسية', 'match' => 'home',
         'icon' => '<path d="M4 11l8-7 8 7v8a1 1 0 01-1 1h-4v-6h-6v6H5a1 1 0 01-1-1z"/>'],
        ['route' => 'events.mine', 'label' => 'أحداثي', 'match' => 'events.mine',
         'icon' => '<rect x="4" y="5" width="16" height="15" rx="3"/><path d="M4 10h16M9 3v4M15 3v4"/>'],
        ['route' => 'notifications', 'label' => 'الإشعارات', 'match' => 'notifications',
         'icon' => '<path d="M6 16V11a6 6 0 0112 0v5l1.5 2h-15z"/><path d="M10 20.5a2 2 0 004 0"/>'],
        ['route' => 'profile', 'label' => 'بروفايلي', 'match' => 'profile',
         'icon' => '<circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6"/>'],
    ];
@endphp

<nav class="liqa-nav" aria-label="التنقل الرئيسي">
    <div class="liqa-nav__inner">
        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}"
               class="liqa-nav__item {{ request()->routeIs($item['match']) ? 'is-active' : '' }}"
               @if (request()->routeIs($item['match'])) aria-current="page" @endif>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>
