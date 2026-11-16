@extends('layouts.app')
@section('title', __('الإشعارات'))
@section('content')
    <div class="mx-auto" style="max-width: 760px">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="h2 liqa-title mb-1">{{ __('الإشعارات') }}</h1>
                <p class="text-secondary mb-0">{{ $unread > 0 ? __('لديك ').$unread.__(' إشعار غير مقروء.') : __('لا توجد إشعارات غير مقروءة.') }}</p>
            </div>
            @if ($unread > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-liqa-outline px-3">{{ __('تعليم الكل كمقروء') }}</button>
                </form>
            @endif
        </div>

        @if ($notifications->isEmpty())
            <div class="liqa-card text-center py-5">
                <p class="fw-bold fs-5 mb-1">{{ __('لا توجد إشعارات بعد') }}</p>
                <p class="text-secondary mb-4">{{ __('ستصلك هنا طلبات الانضمام، وقرارات القبول والرفض، وتعديلات الأحداث.') }}</p>
                <a href="{{ route('events.index') }}" class="btn btn-liqa px-4">{{ __('تصفّح الأحداث') }}</a>
            </div>
        @else
            <div class="d-grid gap-2">
                @foreach ($notifications as $n)
                    <a href="{{ route('notifications.open', $n) }}" class="notif-item {{ $n->isUnread() ? 'is-unread' : '' }}">
                        <span class="notif-dot" aria-hidden="true"></span>
                        <span class="flex-grow-1">
                            <span class="d-block text-white">{{ $n->message }}</span>
                            <span class="d-block small text-secondary mt-1">{{ $n->created_at->diffForHumans() }}</span>
                        </span>
                        @if ($n->isUnread()) <span class="visually-hidden">{{ __('غير مقروء') }}</span> @endif
                    </a>
                @endforeach
            </div>

            <div class="mt-4 d-flex justify-content-center">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
