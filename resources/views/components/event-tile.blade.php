@props(['event', 'manage' => false, 'badgeText' => null, 'badgeClass' => ''])

@php
    $ended = $event->hasEnded();
@endphp

<div class="event-tile h-100 d-flex flex-column">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <span class="liqa-badge {{ $event->typeBadgeClass() }}">{{ $event->typeLabel() }}</span>
        <span class="d-flex flex-wrap gap-2">
            @if ($badgeText) <span class="liqa-badge {{ $badgeClass }}">{{ $badgeText }}</span> @endif
            @if ($event->isCancelled())
                <span class="liqa-badge liqa-badge--red">ملغي</span>
            @elseif ($ended)
                <span class="liqa-badge">انتهى</span>
            @else
                <span class="liqa-badge liqa-badge--green">قادم</span>
            @endif
        </span>
    </div>

    <h3 class="h5 liqa-title text-white mb-3">{{ $event->title }}</h3>

    <ul class="meta-list mb-3">
        <li><x-icon name="calendar" :size="16" /> {{ $event->whenLabel() }}</li>
        <li><x-icon name="pin" :size="16" /> <span class="text-truncate">{{ $event->placeLabel() }}</span></li>
        <li class="text-success-emphasis"><x-icon name="check" :size="16" /> <bdi dir="ltr">{{ $event->seatsTaken() }} / {{ $event->capacity }}</bdi> مشاركين مقبولين</li>
    </ul>

    <div class="d-flex gap-2 mt-auto">
        @if ($manage && ! $event->isCancelled() && ! $ended)
            <a href="{{ route('events.edit', $event) }}" class="btn btn-liqa-outline icon-only" aria-label="تعديل الحدث" title="تعديل">
                <x-icon name="edit" :size="18" />
            </a>
        @endif
        <a href="{{ route('events.show', $event) }}" class="btn btn-liqa flex-grow-1 d-flex align-items-center justify-content-center gap-2">
            {{ $manage ? 'إدارة' : 'عرض' }} <x-icon name="eye" :size="18" />
        </a>
    </div>
</div>
