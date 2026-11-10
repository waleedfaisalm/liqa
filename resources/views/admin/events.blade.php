@extends('layouts.admin')

@section('title', 'إدارة الأحداث')

@section('content')
    @if (auth()->user()->hasPermission('export'))
    <div class="mb-3"><a href="{{ route('admin.export', 'events') }}" class="btn btn-sm btn-liqa-outline px-3">تصدير CSV</a></div>
    @endif
    <div class="mb-4">
        <h1 class="h2 liqa-title mb-1">الأحداث</h1>
        <p class="text-secondary mb-0">تصفّح أحداث المنصة، وألغِ أي حدث مخالف مع إشعار المنظّم والمشاركين.</p>
    </div>

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="search" name="q" value="{{ $search }}" class="form-control" style="max-width: 360px" placeholder="ابحث بعنوان الحدث أو اسم المنظّم">
        <button class="btn btn-liqa px-4" type="submit">بحث</button>
    </form>

    <div class="tab-pills mb-3">
        @foreach (['upcoming' => 'قادمة', 'past' => 'منتهية', 'cancelled' => 'ملغاة', 'all' => 'الكل'] as $key => $label)
            <a href="{{ route('admin.events', ['tab' => $key, 'q' => $search ?: null]) }}" class="tab-pill {{ $tab === $key ? 'is-active' : '' }}">{{ $label }} <span class="count">{{ $counts[$key] }}</span></a>
        @endforeach
    </div>

    <div class="liqa-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>الحدث</th>
                        <th>المنظّم</th>
                        <th>الموعد</th>
                        <th>المشاركون</th>
                        <th>الحالة</th>
                        <th class="text-end">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td data-label="الحدث">
                                <a href="{{ route('events.show', $event) }}" class="fw-bold text-white text-decoration-none">{{ $event->title }}</a>
                                <div class="small text-secondary">{{ $event->typeLabel() }} · {{ $event->modeLabel() }}</div>
                            </td>
                            <td data-label="المنظّم">
                                <a href="{{ route('users.show', $event->host) }}" class="text-decoration-none">{{ $event->host->name }}</a>
                            </td>
                            <td data-label="الموعد" class="small">{{ $event->whenLabel() }}</td>
                            <td data-label="المشاركون"><bdi dir="ltr">{{ $event->accepted_requests_count }} / {{ $event->capacity }}</bdi></td>
                            <td data-label="الحالة">
                                @if ($event->isCancelled()) <span class="liqa-badge liqa-badge--red">ملغي</span>
                                @elseif ($event->hasEnded()) <span class="liqa-badge">انتهى</span>
                                @else <span class="liqa-badge liqa-badge--green">قادم</span>
                                @endif
                            </td>
                            <td data-label="الإجراءات">
                                <div class="d-flex flex-wrap gap-1 justify-content-end">
                                    <a href="{{ route('events.show', $event) }}" class="btn btn-sm btn-liqa-outline px-3">عرض</a>
                                    @if (! $event->isCancelled() && ! $event->hasEnded())
                                        <form method="POST" action="{{ route('admin.events.cancel', $event) }}"
                                              onsubmit="return confirm('إلغاء هذا الحدث وإشعار المنظّم والمشاركين؟')">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-liqa-danger px-3">إلغاء</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">لا توجد أحداث.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $events->links() }}</div>
@endsection
