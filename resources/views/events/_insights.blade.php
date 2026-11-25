@php
    $pred = \App\Support\Reliability::predict($event);
    $extra = \App\Support\Reliability::extraSeats($event);
    $taken = $pred['accepted'];
@endphp
@if ($taken > 0 || $extra > 0)
<div class="liqa-card p-3 p-md-4 mt-3" id="insights">
    <h2 class="h5 liqa-title mb-2 d-flex align-items-center gap-2"><x-icon name="trend" :size="20" /> {{ __('توقع الحضور') }}</h2>
    @if ($taken > 0)
        <p class="mb-2">{{ __('مقبول :n، والمتوقع حضوره فعليًا نحو :e حسب سجل الأعضاء.', ['n' => $taken, 'e' => $pred['expected']]) }}</p>
    @endif
    @if ($pred['risky']->isNotEmpty())
        <p class="small text-secondary mb-1">{{ __('أعضاء سبق لهم الغياب:') }}</p>
        <ul class="small mb-2">@foreach ($pred['risky']->take(5) as $i) <li>{{ $i['user']->name }} <span class="hint">({{ __('غاب :m من :e', ['m' => $i['missed'], 'e' => $i['eligible']]) }})</span></li> @endforeach</ul>
    @endif
    @if ($extra > 0)
        <div class="alert alert-info py-2 small mb-0">{{ __('الحدث ممتلئ وله قائمة انتظار ونتوقع غياب بعض المسجلين، يمكنك رفع السعة بنحو :n مقعد.', ['n' => $extra]) }} <a href="{{ route('events.edit', $event) }}">{{ __('تعديل السعة') }}</a></div>
    @endif
</div>
@endif
