@if (\App\Support\LiveKit::configured() && ! $event->isCancelled() && ($event->roomIsOpen() || ! $event->hasEnded()))
    @if ($event->roomIsOpen())
        <a href="{{ route('events.room', $event) }}" class="btn btn-liqa w-100 mb-2 d-flex align-items-center justify-content-center gap-2">
            الدخول إلى الغرفة المباشرة <x-icon name="send" :size="17" />
        </a>
    @else
        <div class="small text-secondary mb-3">الغرفة المباشرة (صوت وفيديو) تفتح قبل الموعد بـ {{ \App\Models\Event::ROOM_OPENS_BEFORE_MINUTES }} دقيقة.</div>
    @endif
@endif
