@extends('layouts.admin')

@section('title', 'إدارة البلاغات')

@section('content')
    @if (auth()->user()->hasPermission('export'))
    <div class="mb-3"><a href="{{ route('admin.export', 'reports') }}" class="btn btn-sm btn-liqa-outline px-3">تصدير CSV</a></div>
    @endif
    <div class="mb-4">
        <span class="eyebrow eyebrow--pink mb-3"><span class="dot"></span>لوحة الإدارة</span>
        <h1 class="h2 liqa-title mb-1">البلاغات</h1>
        <p class="text-secondary mb-0">راجع البلاغات المرسلة من الأعضاء، واتخذ الإجراء المناسب.</p>
    </div>

    <div class="tab-pills mb-4">
        @foreach (['open' => 'مفتوحة', 'reviewed' => 'تمت مراجعتها', 'all' => 'الكل'] as $key => $label)
            <a href="{{ route('admin.reports', ['tab' => $key]) }}" class="tab-pill {{ $tab === $key ? 'is-active' : '' }}">{{ $label }} ({{ $counts[$key] }})</a>
        @endforeach
    </div>

    @if ($reports->isEmpty())
        <div class="liqa-card text-center py-5">
            <p class="fw-bold fs-5 mb-1">لا توجد بلاغات في هذا القسم</p>
            <p class="text-secondary mb-0">ستظهر البلاغات الجديدة هنا فور وصولها.</p>
        </div>
    @else
        <div class="d-grid gap-3">
            @foreach ($reports as $report)
                @php
                    $target = $report->target();
                    $isUser = $report->target_type === 'user';
                @endphp
                <div class="liqa-card p-3 p-md-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @php $urg = $report->status === 'open' ? ($urgent[$report->target_type.':'.$report->target_id] ?? 0) : 0; @endphp
                            @if ($urg) <span class="liqa-badge liqa-badge--red">عاجل · {{ $urg }} بلاغات على الطرف نفسه</span> @endif
                            <span class="liqa-badge">{{ $isUser ? 'مستخدم' : 'حدث' }}</span>
                            <span class="liqa-badge liqa-badge--pink">{{ \App\Models\Report::REASONS[$report->reason] ?? $report->reason }}</span>
                            <span class="liqa-badge {{ $report->status === 'open' ? 'liqa-badge--sky' : 'liqa-badge--green' }}">{{ $report->status === 'open' ? 'مفتوح' : 'تمت المراجعة' }}</span>
                        </div>
                        <span class="small hint">{{ $report->created_at->translatedFormat('j F Y · g:i a') }}</span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="small hint mb-1">المُبلَّغ عنه</div>
                            @if (! $target)
                                <span class="text-secondary">لم يعد موجودًا (حُذف)</span>
                            @elseif ($isUser)
                                <a href="{{ route('users.show', $target) }}" class="fw-bold text-white text-decoration-none">{{ $target->name }}</a>
                                <div class="small text-secondary">{{ $target->email }}</div>
                                <a href="{{ route('admin.users', ['q' => $target->email]) }}" class="small">إدارة هذا المستخدم</a>
                            @else
                                <a href="{{ route('events.show', $target) }}" class="fw-bold text-white text-decoration-none">{{ $target->title }}</a>
                                <div class="small text-secondary">المنظّم: {{ $target->host->name }}</div>
                                <a href="{{ route('admin.events', ['tab' => 'all', 'q' => $target->title]) }}" class="small">إدارة هذا الحدث</a>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <div class="small hint mb-1">المُبلِّغ</div>
                            @if ($report->reporter)
                                <a href="{{ route('users.show', $report->reporter) }}" class="fw-bold text-white text-decoration-none">{{ $report->reporter->name }}</a>
                                <div class="small text-secondary">{{ $report->reporter->email }}</div>
                            @else
                                <span class="text-secondary">حساب محذوف</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="small hint mb-1">التفاصيل</div>
                        <p class="mb-0 text-break" style="white-space: pre-line">{{ $report->details ?: 'لم تُكتب تفاصيل.' }}</p>
                    </div>

                    @if (\App\Support\Ai::enabled())
                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-liqa-outline" onclick="aiReport(this, {{ $report->id }})">✨ تحليل بالذكاء الاصطناعي</button>
                            <div class="small mt-2 text-secondary ai-out" style="white-space: pre-line" id="ai-r-{{ $report->id }}"></div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.reports.update', $report) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn {{ $report->status === 'open' ? 'btn-liqa' : 'btn-liqa-outline' }} px-4">
                            {{ $report->status === 'open' ? 'تمت المراجعة' : 'إعادة فتح البلاغ' }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $reports->links() }}</div>
    @endif
@endsection

@push('scripts')
<script>
    function aiReport(btn, id) {
        var out = document.getElementById('ai-r-' + id);
        btn.disabled = true; out.textContent = 'جارٍ التحليل...';
        fetch('{{ url('/admin/reports') }}/' + id + '/ai', { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(function (r) { return r.json(); })
            .then(function (j) { out.textContent = (j.text || j.message || 'تعذّر التحليل.') + (j.ok ? '\n(اقتراح آلي — القرار لك)' : ''); })
            .catch(function () { out.textContent = 'تعذّر الاتصال.'; })
            .finally(function () { btn.disabled = false; });
    }
</script>
@endpush
