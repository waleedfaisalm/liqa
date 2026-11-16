@props(['event', 'user' => null])
@php
    $myRequest = $user ? $event->requestOf($user) : null;
    $isHost = $event->isHostedBy($user);
    $taken = $event->seatsTaken();
    $percent = $event->capacity > 0 ? min(100, round($taken / $event->capacity * 100)) : 0;
@endphp

<div class="position-relative h-100">
@if ($user && ! $isHost)
    @php $saved = $user->hasSaved($event); @endphp
    <form method="POST" action="{{ route('events.save', $event) }}" class="save-form js-save-form">
        @csrf
        <button type="submit" class="save-btn {{ $saved ? 'is-saved' : '' }}" aria-label="{{ $saved ? __('إزالة من المحفوظات') : __('حفظ الحدث') }}" title="{{ $saved ? __('إزالة من المحفوظات') : __('حفظ الحدث') }}">
            <x-icon name="bookmark" :size="18" />
        </button>
    </form>
@endif
<a href="{{ route('events.show', $event) }}" class="event-card liqa-card h-100 d-flex flex-column gap-3 text-decoration-none">
    <div class="d-flex flex-wrap gap-2">
        <span class="liqa-badge {{ $event->typeBadgeClass() }}">{{ $event->typeLabel() }}</span>
        <span class="liqa-badge liqa-badge--green">{{ $event->modeLabel() }}</span>
        @if ($event->isCancelled())
            <span class="liqa-badge liqa-badge--red">{{ __('ملغي') }}</span>
        @elseif ($isHost)
            <span class="liqa-badge">{{ __('أنت المنظّم') }}</span>
        @elseif ($myRequest?->status === 'accepted')
            <span class="liqa-badge liqa-badge--green">{{ __('مقبول') }}</span>
        @elseif ($myRequest?->status === 'pending')
            <span class="liqa-badge liqa-badge--pink">{{ __('طلبك قيد المراجعة') }}</span>
        @elseif ($myRequest?->status === 'waitlisted')
            <span class="liqa-badge liqa-badge--sky">{{ __('في قائمة الانتظار') }}</span>
        @elseif ($myRequest?->status === 'rejected')
            <span class="liqa-badge liqa-badge--red">{{ __('مرفوض') }}</span>
        @endif
    </div>

    <div>
        <h3 class="h5 liqa-title text-white mb-2">{{ $event->title }}</h3>
        <div class="small text-secondary">{{ $event->whenLabel() }}</div>
        <div class="small text-secondary text-truncate">{{ $event->cardPlaceLabel() }}</div>
    </div>

    <div class="mt-auto">
        <div class="d-flex justify-content-between small mb-1">
            <span class="text-secondary">{{ __('الأماكن') }}</span>
            <span class="fw-bold text-white"><bdi dir="ltr">{{ $taken }} / {{ $event->capacity }}</bdi></span>
        </div>
        <div class="seat-meter mb-3"><span style="width: {{ $percent }}%"></span></div>
        <div class="d-flex align-items-center gap-2">
            <x-avatar :user="$event->host" :size="28" />
            <span class="small text-secondary">{{ $event->host->name }}</span>
        </div>
    </div>
</a>
</div>
