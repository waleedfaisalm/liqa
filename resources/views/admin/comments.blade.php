@extends('layouts.admin')

@section('title', 'التعليقات')

@section('content')
    <div class="mb-4">
        <h1 class="h2 liqa-title mb-1">التعليقات</h1>
        <p class="text-secondary mb-0">راجع أحدث تعليقات الأحداث، واحذف أي تعليق مخالف.</p>
    </div>

    <div class="tab-pills mb-3">
        <a href="{{ route('admin.comments') }}" class="tab-pill is-active">التعليقات</a>
        <a href="{{ route('admin.ratings') }}" class="tab-pill">التقييمات</a>
    </div>

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="search" name="q" value="{{ $search }}" class="form-control" style="max-width: 360px" placeholder="ابحث في نص التعليق أو اسم الكاتب">
        <button class="btn btn-liqa px-4" type="submit">بحث</button>
    </form>

    <div class="liqa-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="admin-table">
                <thead><tr><th>الكاتب</th><th>التعليق</th><th>الحدث</th><th>الوقت</th><th class="text-end">الإجراء</th></tr></thead>
                <tbody>
                    @forelse ($comments as $c)
                        <tr>
                            <td>
                                @if ($c->user) <a href="{{ route('admin.users.show', $c->user) }}">{{ $c->user->name }}</a> @else — @endif
                            </td>
                            <td style="max-width: 380px; white-space: normal">{{ $c->body }}</td>
                            <td>
                                @if ($c->event) <a href="{{ route('events.show', $c->event) }}">{{ \Illuminate\Support\Str::limit($c->event->title, 40) }}</a> @else — @endif
                            </td>
                            <td class="small text-secondary">{{ $c->created_at->diffForHumans() }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.comments.destroy', $c) }}" onsubmit="return confirm('حذف هذا التعليق نهائيًا؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-liqa-danger px-3">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">لا توجد تعليقات.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $comments->links() }}</div>
@endsection
