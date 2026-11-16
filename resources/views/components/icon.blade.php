@props(['name', 'size' => 16])
@php
    $paths = [
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="3"/><path d="M4 10h16M9 3v4M15 3v4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'pin' => '<path d="M12 21s7-6.2 7-11.5A7 7 0 005 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-5.5 6.5-5.5s6.5 1.9 6.5 5.5"/><path d="M16 4.8a3.5 3.5 0 010 6.4M18 14.8c2.2.5 3.5 2.2 3.5 5.2"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6"/>',
        'star' => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16z"/>',
        'eye' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'plus' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        'bookmark' => '<path d="M6 4h12v17l-6-4-6 4z"/>',
        'chat' => '<path d="M4 5h16v11H9l-5 4z"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-8M22 20H2"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l3 3 5-6"/>',
        'link' => '<path d="M10 14a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 00-5.7 0l-3 3a4 4 0 005.7 5.7l1-1"/>',
        'rocket' => '<path d="M5 15c-1.5 1.5-2 4-2 5 1 0 3.5-.5 5-2"/><path d="M9 15l-3-3c1-3 4-7 9-8 3-.5 5-.5 5-.5s0 2-.5 5c-1 5-5 8-8 9z"/><circle cx="15" cy="9" r="1.5"/>',
        'play' => '<circle cx="12" cy="12" r="9"/><path d="M10 8.5l5 3.5-5 3.5z"/>',
        'question' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 114 2c-.9.6-1.5 1.1-1.5 2.2M12 17h.01"/>',
        'trend' => '<path d="M3 20h18M5 16l4-5 3 3 5-7"/><path d="M14 7h3v3"/>',
        'coffee' => '<path d="M4 9h12v5a5 5 0 01-5 5H9a5 5 0 01-5-5z"/><path d="M16 10h2a2 2 0 010 4h-2M8 3v3M12 3v3"/>',
        'thumb' => '<path d="M7 11v9H4v-9zM7 11l4-8c1.5 0 2.5 1 2.5 2.5V9h5a2 2 0 012 2.3l-1.3 7A2 2 0 0117.2 20H7"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4-4"/>',
        'user-plus' => '<circle cx="9" cy="8" r="4"/><path d="M2.5 20c0-3.6 2.9-5.5 6.5-5.5s6.5 1.9 6.5 5.5M19 8v6M16 11h6"/>',
        'shield' => '<path d="M12 3l8 3v6c0 4.5-3.2 7.7-8 9-4.8-1.3-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
        'bolt' => '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
        'tag' => '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.2"/>',
        'logout' => '<path d="M10 4H5a1 1 0 00-1 1v14a1 1 0 001 1h5M15 8l4 4-4 4M19 12H9"/>',
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/>',
        'send' => '<path d="M21 3L10 14M21 3l-7 18-4-7-7-4z"/>',
    ];
@endphp

<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>{!! $paths[$name] ?? '' !!}</svg>
