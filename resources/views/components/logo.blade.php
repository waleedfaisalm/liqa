@props(['size' => 56])
@php $gid = 'lg'.substr(md5(uniqid('', true)), 0, 6); @endphp
<svg class="liqa-logo" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 64 64" role="img" aria-label="{{ __('شعار لقاء') }}" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="{{ $gid }}" x1="6" y1="4" x2="58" y2="60" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#9333ea"/><stop offset="1" stop-color="#db2777"/>
        </linearGradient>
    </defs>
    <rect width="64" height="64" rx="19" fill="url(#{{ $gid }})"/>
    <g fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="6.5">
        <path d="M42 26v11c0 7-4.5 11.5-11 11.5" stroke="#f1c9ee"/>
        <path d="M22 26v11c0 7 4.5 11.5 11 11.5" stroke="#fff"/>
    </g>
    <circle cx="42" cy="15.5" r="4.6" fill="#f1c9ee"/>
    <circle cx="22" cy="15.5" r="4.6" fill="#fff"/>
</svg>
