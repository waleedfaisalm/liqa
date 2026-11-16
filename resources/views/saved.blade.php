@extends('layouts.app')
@section('title', __('الأحداث المحفوظة'))
@section('content')
    <div class="mb-4">
        <span class="eyebrow mb-3"><span class="dot"></span>{{ __('المحفوظة') }}</span>
        <h1 class="display-6 liqa-title mb-1">{{ __('أحداثك') }} <span class="text-gradient">{{ __('المحفوظة') }}</span></h1>
        <p class="text-secondary mb-0">{{ __('الأحداث التي حفظتها لتعود إليها لاحقًا.') }}</p>
    </div>

    @if ($upcoming->isEmpty() && $past->isEmpty())
        <div class="liqa-card text-center py-5">
            <p class="fw-bold fs-5 mb-1">{{ __('لا توجد أحداث محفوظة') }}</p>
            <p class="text-secondary mb-4">{{ __('اضغط على أيقونة الحفظ في أي حدث ليظهر هنا.') }}</p>
            <a href="{{ route('events.index') }}" class="btn btn-liqa px-4">{{ __('تصفّح الأحداث') }}</a>
        </div>
    @else
        @if ($upcoming->isNotEmpty())
            <div class="row g-3 mb-5">
                @foreach ($upcoming as $event)
                    <div class="col-md-6 col-lg-4"><x-event-card :event="$event" :user="auth()->user()" /></div>
                @endforeach
            </div>
        @endif

        @if ($past->isNotEmpty())
            <h2 class="h5 liqa-title mb-3">{{ __('منتهية أو ملغاة') }}</h2>
            <div class="row g-3">
                @foreach ($past as $event)
                    <div class="col-md-6 col-lg-4"><x-event-card :event="$event" :user="auth()->user()" /></div>
                @endforeach
            </div>
        @endif
    @endif
@endsection
