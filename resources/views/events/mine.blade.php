@extends('layouts.app')
@section('title', __('أحداثي'))
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3"><span class="dot"></span>{{ __('أحداثي') }}</span>
            <h1 class="display-6 liqa-title mb-0">{{ __('إدارة') }} <span class="text-gradient">{{ __('أحداثك ومشاركاتك') }}</span></h1>
        </div>
        <a href="{{ route('events.create') }}" class="btn btn-liqa px-4 d-flex align-items-center gap-2">
            <x-icon name="plus" :size="18" /> {{ __('انشر حدثًا جديدًا') }}
        </a>
    </div>

    <div class="tab-pills mb-4" role="tablist">
        <a href="{{ route('events.mine') }}" class="tab-pill {{ $tab === 'hosted' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $tab === 'hosted' ? 'true' : 'false' }}">
            <x-icon name="users" :size="18" /> {{ __('أحداث أنظّمها') }} <span class="count">{{ $hosted->count() }}</span>
        </a>
        <a href="{{ route('events.mine', ['tab' => 'joined']) }}" class="tab-pill {{ $tab === 'joined' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $tab === 'joined' ? 'true' : 'false' }}">
            <x-icon name="calendar" :size="18" /> {{ __('أحداث منضمّ إليها') }} <span class="count">{{ $joined->count() }}</span>
        </a>
    </div>

    @if ($tab === 'hosted')
        @if ($hosted->isEmpty())
            <div class="liqa-card text-center py-5">
                <p class="fw-bold fs-5 mb-1">{{ __('لم تنشر أي حدث بعد') }}</p>
                <p class="text-secondary mb-4">{{ __('انشر أول لقاء لك وابدأ باستقبال طلبات الانضمام.') }}</p>
                <a href="{{ route('events.create') }}" class="btn btn-liqa px-4">{{ __('انشر حدثًا') }}</a>
            </div>
        @else
            <div class="row g-3">
                @foreach ($hosted as $event)
                    @php
                        $pendingText = $event->pending_requests_count > 0 && ! $event->isCancelled() && ! $event->hasEnded()
                            ? $event->pending_requests_count.__(' طلب جديد') : null;
                    @endphp
                    <div class="col-md-6">
                        <x-event-tile :event="$event" :manage="true" :badge-text="$pendingText" badge-class="liqa-badge--pink" />
                    </div>
                @endforeach
            </div>
        @endif
    @else
        @if ($joined->isEmpty())
            <div class="liqa-card text-center py-5">
                <p class="fw-bold fs-5 mb-1">{{ __('لم تقدّم طلب انضمام بعد') }}</p>
                <p class="text-secondary mb-4">{{ __('تصفّح الأحداث القادمة وقدّم طلبك.') }}</p>
                <a href="{{ route('events.index') }}" class="btn btn-liqa px-4">{{ __('تصفّح الأحداث') }}</a>
            </div>
        @else
            @php $labels = ['pending' => [__('قيد المراجعة'), 'liqa-badge--pink'], 'accepted' => [__('مقبول'), 'liqa-badge--green'], 'rejected' => [__('مرفوض'), 'liqa-badge--red'], 'waitlisted' => [__('قائمة الانتظار'), 'liqa-badge--sky']]; @endphp
            <div class="row g-3">
                @foreach ($joined as $req)
                    @php [$label, $class] = $labels[$req->status]; @endphp
                    <div class="col-md-6">
                        <x-event-tile :event="$req->event" :badge-text="$label" :badge-class="$class" />
                    </div>
                @endforeach
            </div>
        @endif
    @endif
@endsection
