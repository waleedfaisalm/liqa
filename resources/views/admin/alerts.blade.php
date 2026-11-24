@extends('layouts.admin')

@section('title', 'التنبيهات')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="h2 liqa-title mb-1">التنبيهات</h1>
            <p class="text-secondary mb-0">تنبيهات تحتاج مراجعتك: بلاغات متكررة وحسابات عالية الخطورة.</p>
        </div>
        <form method="POST" action="{{ route('admin.alerts.read-all') }}">
            @csrf
            <button class="btn btn-sm btn-liqa-outline px-3">تعليم الكل كمقروء</button>
        </form>
    </div>

    @if ($alerts->isEmpty())
        <div class="liqa-card p-4 text-center text-secondary">لا توجد تنبيهات. كل شيء هادئ ✓</div>
    @else
        <div class="d-grid gap-2">
            @foreach ($alerts as $a)
                @php $isReport = $a->type === 'report_urgent'; @endphp
                <a href="{{ route('admin.alerts.open', $a) }}" class="liqa-card p-3 text-decoration-none d-flex align-items-start gap-3 {{ $a->isUnread() ? '' : 'opacity-75' }}">
                    <span class="liqa-badge {{ $isReport ? 'liqa-badge--red' : 'liqa-badge--pink' }} flex-shrink-0">{{ $isReport ? 'بلاغات متكررة' : 'خطورة حساب' }}</span>
                    <span class="min-w-0 me-auto">
                        <span class="d-block text-white {{ $a->isUnread() ? 'fw-bold' : '' }}">{{ $a->message }}</span>
                        <span class="d-block small text-secondary">{{ $a->created_at->diffForHumans() }}</span>
                    </span>
                    @if ($a->isUnread()) <span class="presence-dot flex-shrink-0 mt-2" title="غير مقروء"></span> @endif
                </a>
            @endforeach
        </div>
        <div class="mt-3">{{ $alerts->links() }}</div>
    @endif
@endsection
