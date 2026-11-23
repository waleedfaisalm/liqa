@extends('layouts.app')
@section('title', __('تسجيل الحضور'))
@section('content')
<div class="mx-auto" style="max-width: 820px">
    <a href="{{ route('events.show', $event) }}" class="small text-secondary text-decoration-none">{{ __('← العودة إلى صفحة الحدث') }}</a>
    <h1 class="h3 liqa-title mt-2 mb-1">{{ __('تسجيل الحضور') }} — {{ $event->title }}</h1>
    <p class="hint mb-4">{{ __('اعرض هذه الشاشة على الحاضرين ليمسحوا الرمز بكاميرا جوالهم. يتجدد الرمز تلقائيًا ولا يصلح للمشاركة عن بُعد.') }}</p>

    <div class="row g-3" id="checkin"
         data-qr-url="{{ route('events.checkin.qr', $event) }}"
         data-status-url="{{ route('events.checkin.status', $event) }}">
        <div class="col-md-6">
            <div class="liqa-card p-4 text-center h-100">
                <canvas id="checkinQr" class="rounded-3 d-none" style="max-width: 100%; height: auto; background: #fff"></canvas>
                <div id="checkinMsg" class="text-secondary">{{ __('جارٍ تحميل الرمز…') }}</div>
                <div class="small hint mt-3">{{ __('يفتح التسجيل قبل الموعد بساعة ويُغلق بعد النهاية بساعة.') }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="liqa-card p-3 p-md-4 h-100">
                <h2 class="h5 liqa-title mb-1">{{ __('المقبولون') }}</h2>
                <p class="small hint mb-3">{{ __('حضر') }} <b class="text-white" id="checkinCount">{{ count($checked) }}</b> {{ __('من') }} {{ $accepted->count() }}</p>
                @if ($accepted->isEmpty())
                    <p class="text-secondary mb-0">{{ __('لا يوجد مشاركون مقبولون بعد.') }}</p>
                @else
                    <div class="d-grid gap-2">
                        @foreach ($accepted as $u)
                            @php $done = in_array($u->id, $checked, true); @endphp
                            <div class="request-row" data-user-id="{{ $u->id }}">
                                <span class="d-flex align-items-center gap-2 me-auto">
                                    <x-avatar :user="$u" :size="34" />
                                    <span class="small fw-bold text-white">{{ $u->name }}</span>
                                </span>
                                <span data-done class="liqa-badge liqa-badge--green {{ $done ? '' : 'd-none' }}">✓ {{ __('حضر') }}</span>
                                <form method="POST" action="{{ route('events.checkin.manual', [$event, $u]) }}" data-manual class="{{ $done ? 'd-none' : '' }}">
                                    @csrf
                                    <button class="btn btn-liqa-outline btn-sm px-3" style="min-height:2rem">{{ __('تسجيل يدوي') }}</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@vite('resources/js/checkin.js')
@endsection
