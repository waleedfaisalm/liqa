@extends('layouts.app')
@section('title', __('الإشعارات'))
@section('content')
    <div class="mx-auto" style="max-width: 760px">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="h2 liqa-title mb-1">{{ __('الإشعارات') }}</h1>
                <p class="text-secondary mb-0">{{ $unread > 0 ? __('لديك ').$unread.__(' إشعار غير مقروء.') : __('لا توجد إشعارات غير مقروءة.') }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($unread > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="btn btn-liqa-outline px-3">{{ __('تعليم الكل كمقروء') }}</button>
                    </form>
                @endif
                @if ($notifications->isNotEmpty())
                    <form method="POST" action="{{ route('notifications.clear') }}" onsubmit="return confirm('{{ addslashes(__('سيتم حذف جميع إشعاراتك نهائيًا ولا يمكن التراجع. هل أنت متأكد؟')) }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-liqa-danger px-3">{{ __('حذف الكل') }}</button>
                    </form>
                @endif
            </div>
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
                    <div class="notif-row">
                        <a href="{{ route('notifications.open', $n) }}" class="notif-item {{ $n->isUnread() ? 'is-unread' : '' }}">
                            <span class="notif-dot" aria-hidden="true"></span>
                            <span class="flex-grow-1">
                                <span class="d-block text-white">{{ $n->message }}</span>
                                <span class="d-block small text-secondary mt-1">{{ $n->created_at->diffForHumans() }}</span>
                            </span>
                            @if ($n->isUnread()) <span class="visually-hidden">{{ __('غير مقروء') }}</span> @endif
                        </a>
                        <form method="POST" action="{{ route('notifications.destroy', $n) }}" onsubmit="return confirm('{{ addslashes(__('حذف هذا الإشعار نهائيًا؟')) }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="notif-del" aria-label="{{ __('حذف الإشعار') }}" title="{{ __('حذف الإشعار') }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"/></svg>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 d-flex justify-content-center">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
