@extends('layouts.admin')

@section('title', 'سجل الإجراءات')

@section('content')
    <div class="mb-4">
        <h1 class="h2 liqa-title mb-1">سجل الإجراءات</h1>
        <p class="text-secondary mb-0">أرشيف بما نفّذه المديرون في لوحة الإدارة، للمراجعة والمساءلة.</p>
    </div>

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <select name="action" class="form-select" style="max-width: 260px" onchange="this.form.submit()">
            <option value="">كل الإجراءات</option>
            @foreach (\App\Models\AdminLog::ACTIONS as $key => $meta)
                <option value="{{ $key }}" @selected($action === $key)>{{ $meta[0] }}</option>
            @endforeach
        </select>
        <select name="admin" class="form-select" style="max-width: 260px" onchange="this.form.submit()">
            <option value="">كل المديرين</option>
            @foreach ($admins as $a)
                <option value="{{ $a->id }}" @selected((string) $adminId === (string) $a->id)>{{ $a->name }}</option>
            @endforeach
        </select>
        @if ($action || $adminId) <a href="{{ route('admin.logs') }}" class="btn btn-liqa-outline">إزالة التصفية</a> @endif
    </form>

    <div class="liqa-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="admin-table">
                <thead><tr><th>الوقت</th><th>المدير</th><th>الإجراء</th><th>الهدف</th><th>التفاصيل</th><th>عنوان IP</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="small text-secondary" title="{{ $log->created_at }}">{{ $log->created_at->translatedFormat('j F، g:i a') }}</td>
                            <td>{{ $log->admin_name ?: '—' }}</td>
                            <td><span class="liqa-badge {{ $log->actionClass() }}">{{ $log->actionLabel() }}</span></td>
                            <td>
                                @if ($log->target_label)
                                    @if ($log->target_type === 'user' && $log->target_id)
                                        <a href="{{ route('admin.users.show', $log->target_id) }}">{{ $log->target_label }}</a>
                                    @elseif ($log->target_type === 'event' && $log->target_id)
                                        <a href="{{ route('events.show', $log->target_id) }}">{{ $log->target_label }}</a>
                                    @elseif ($log->target_type === 'report')
                                        <a href="{{ route('admin.reports') }}">{{ $log->target_label }}</a>
                                    @else {{ $log->target_label }} @endif
                                @else — @endif
                            </td>
                            <td class="small text-secondary">{{ $log->details ?: '—' }}</td>
                            <td class="small text-secondary" dir="ltr">{{ $log->ip ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">لا توجد إجراءات مسجّلة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
