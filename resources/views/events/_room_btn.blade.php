@if (\App\Support\LiveKit::configured() && ! $event->isCancelled() && ($event->roomIsOpen() || ! $event->hasEnded()))
    @if ($event->roomIsOpen())
        <a href="{{ route('events.room', $event) }}" class="btn btn-liqa w-100 mb-2 d-flex align-items-center justify-content-center gap-2">
            {{ __('الدخول إلى الغرفة المباشرة') }} <x-icon name="send" :size="17" />
        </a>
    @else
        <div class="small text-secondary mb-3">{{ __('الغرفة المباشرة (صوت وفيديو) تفتح قبل الموعد بـ :v1 دقيقة.', ['v1' => \App\Models\Event::ROOM_OPENS_BEFORE_MINUTES]) }}</div>
    @endif
@endif
@if (\App\Support\LiveKit::configured() && ! $event->isCancelled() && $event->isHostedBy(auth()->user()) && ! $event->hasEnded())
    <button type="button" class="btn btn-liqa-outline btn-sm w-100 mb-2"
            onclick="(function(b){var u='{{ route('events.room', $event) }}';(navigator.clipboard?navigator.clipboard.writeText(u):Promise.reject()).then(function(){b.textContent='تم نسخ الرابط ✓';setTimeout(function(){b.textContent='نسخ رابط الغرفة للدعوة'},2000)}).catch(function(){window.prompt('انسخ الرابط:',u)})})(this)">{{ __('نسخ رابط الغرفة للدعوة') }}</button>
@endif
