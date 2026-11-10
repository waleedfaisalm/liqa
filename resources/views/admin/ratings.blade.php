@extends('layouts.admin')

@section('title', 'التقييمات')

@section('content')
    <div class="mb-4">
        <h1 class="h2 liqa-title mb-1">التقييمات</h1>
        <p class="text-secondary mb-0">راجع التقييمات المنشورة، واحذف أي تقييم مسيء أو كيدي.</p>
    </div>

    <div class="tab-pills mb-3">
        <a href="{{ route('admin.comments') }}" class="tab-pill">التعليقات</a>
        <a href="{{ route('admin.ratings') }}" class="tab-pill is-active">التقييمات</a>
    </div>

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="search" name="q" value="{{ $search }}" class="form-control" style="max-width: 360px" placeholder="ابحث في نص التقييم أو الأسماء">
        <button class="btn btn-liqa px-4" type="submit">بحث</button>
    </form>

    <div class="liqa-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="admin-table">
                <thead><tr><th>من</th><th>إلى</th><th>التقييم</th><th>التعليق</th><th>الوقت</th><th class="text-end">الإجراء</th></tr></thead>
                <tbody>
                    @forelse ($ratings as $r)
                        <tr>
                            <td data-label="من">@if ($r->rater) <a href="{{ route('admin.users.show', $r->rater) }}">{{ $r->rater->name }}</a> @else — @endif</td>
                            <td data-label="إلى">@if ($r->ratee) <a href="{{ route('admin.users.show', $r->ratee) }}">{{ $r->ratee->name }}</a> @else — @endif</td>
                            <td data-label="التقييم" class="text-nowrap">{{ $r->stars }} / 5</td>
                            <td data-label="التعليق" style="max-width: 320px; white-space: normal" class="small">{{ $r->comment ?: '—' }}</td>
                            <td data-label="الوقت" class="small text-secondary">{{ $r->created_at->diffForHumans() }}</td>
                            <td data-label="الإجراء" class="text-end">
                                <form method="POST" action="{{ route('admin.ratings.destroy', $r) }}" onsubmit="return confirm('حذف هذا التقييم نهائيًا؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-liqa-danger px-3">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">لا توجد تقييمات.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $ratings->links() }}</div>
@endsection
