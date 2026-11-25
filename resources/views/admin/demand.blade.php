@extends('layouts.admin')

@section('title', 'الطلب والحضور')

@section('content')
    @php $canUsers = auth()->user()->hasPermission('users'); @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="h2 liqa-title mb-1">الطلب والحضور</h1>
            <p class="text-secondary mb-0">أين يوجد أعضاء بلا لقاءات؟ من يصلح منظّمًا؟ متى يمتلئ اللقاء؟ ومن يتكرر غيابه؟</p>
        </div>
        <form method="POST" action="{{ route('admin.demand.sweep') }}">
            @csrf
            <button class="btn btn-sm btn-liqa-outline px-3">تشغيل الفحص الآن</button>
        </form>
    </div>

    {{-- ========== الطلب غير الملبّى ========== --}}
    <div class="liqa-card p-3 p-md-4 mb-4">
        <h2 class="h5 liqa-title mb-1">الطلب غير الملبّى بالمدن</h2>
        <p class="small text-secondary">مدن فيها 3 أعضاء فأكثر وبلا أحداث خلال 90 يومًا (أو أعضاء أكثر بكثير من الأحداث). {{ $noCity }} عضو لم يحدد مدينته بعد.</p>
        @if ($cities->isEmpty())
            <p class="text-secondary mb-0">لا توجد بيانات مدن بعد. سيظهر التحليل عندما يضيف الأعضاء مدنهم من الإعدادات.</p>
        @else
            @php $max = max(1, $cities->max('members')); @endphp
            @foreach ($cities->take(12) as $c)
                @php $gap = $c['members'] >= 3 && ($c['events'] === 0 || $c['ratio'] >= 8); @endphp
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-white fw-bold">{{ $c['name'] }} @if ($gap) <span class="liqa-badge liqa-badge--red ms-1">طلب غير ملبّى</span> @endif</span>
                        <span class="text-secondary">{{ $c['members'] }} عضو · {{ $c['events'] }} حدث (90 يومًا)</span>
                    </div>
                    <div class="progress" style="height:8px"><div class="progress-bar {{ $gap ? 'bg-danger' : '' }}" style="width: {{ round($c['members'] / $max * 100) }}%"></div></div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- ========== ترشيح منظّمين ========== --}}
    @if ($gaps->isNotEmpty())
    <div class="liqa-card p-3 p-md-4 mb-4">
        <h2 class="h5 liqa-title mb-1">مرشّحون لتنظيم لقاء</h2>
        <p class="small text-secondary">أعضاء في مدن الفجوة حضروا لقاءين فأكثر ولم ينظّموا أي لقاء. الترشيح يصلهم كإشعار.</p>
        @foreach ($gaps->take(4) as $gap)
            <div class="fw-bold text-white mt-3 mb-2">{{ $gap['name'] }}</div>
            @forelse ($nominees[$gap['key']] ?? [] as $u)
                <div class="d-flex align-items-center gap-2 mb-2">
                    <a href="{{ route('admin.users.show', $u) }}" class="text-decoration-none flex-grow-1">{{ $u->name }} @if ($u->isVerified()) <span class="liqa-badge liqa-badge--green">موثّق</span> @endif</a>
                    @if (isset($invited[$u->id]))
                        <span class="liqa-badge">تم الترشيح</span>
                    @elseif ($canUsers)
                        <form method="POST" action="{{ route('admin.users.nominate', $u) }}">@csrf<button class="btn btn-sm btn-liqa px-3">رشّحه كمنظّم</button></form>
                    @endif
                </div>
            @empty
                <div class="small text-secondary">لا يوجد مرشّح مناسب بعد في هذه المدينة.</div>
            @endforelse
        @endforeach
    </div>
    @endif

    {{-- ========== أفضل وقت ========== --}}
    <div class="liqa-card p-3 p-md-4 mb-4">
        <h2 class="h5 liqa-title mb-1">أفضل وقت للأحداث</h2>
        <p class="small text-secondary">متوسط امتلاء المقاعد حسب اليوم والفترة، من {{ $pastCount }} حدثًا منتهيًا.</p>
        @if ($slots->isEmpty())
            <p class="text-secondary mb-0">لا توجد أحداث منتهية كافية بعد.</p>
        @else
            @php $best = $slots->first(); @endphp
            <div class="alert alert-info py-2">الأفضل حتى الآن: <strong>{{ $best['day'] }} {{ $best['bucket'] }}</strong> — متوسط امتلاء {{ $best['fill'] }}% ({{ $best['n'] }} حدث).</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th></th>@foreach ($buckets as $b) <th class="text-center small">{{ $b }}</th> @endforeach</tr></thead>
                    <tbody>
                    @foreach ($days as $d => $dn)
                        <tr>
                            <td class="small">{{ $dn }}</td>
                            @foreach ($buckets as $bk => $bn)
                                @php $s = $slots->first(fn ($x) => $x['d'] === $d && $x['b'] === $bk); @endphp
                                <td class="text-center small">@if ($s) <span class="liqa-badge {{ $s['fill'] >= 70 ? 'liqa-badge--green' : ($s['fill'] < 35 ? 'liqa-badge--red' : '') }}">{{ $s['fill'] }}%</span><div class="hint">{{ $s['n'] }}</div> @else <span class="text-secondary">—</span> @endif</td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ========== الغياب ========== --}}
    <div class="liqa-card p-3 p-md-4 mb-4">
        <h2 class="h5 liqa-title mb-1">الالتزام بالحضور</h2>
        <p class="small text-secondary">يُحتسب الغياب فقط في الأحداث التي سُجّل فيها حضور فعلي (غرفة أو QR). التقييد قرار يدوي منك.</p>

        @if ($restricted->isNotEmpty())
            <div class="fw-bold text-white mb-2">مقيّدون حاليًا</div>
            @foreach ($restricted as $u)
                <div class="d-flex align-items-center gap-2 mb-2">
                    <a href="{{ route('admin.users.show', $u) }}" class="text-decoration-none flex-grow-1">{{ $u->name }} <span class="small text-secondary">حتى {{ $u->join_restricted_until->translatedFormat('j F') }}</span></a>
                    @if ($canUsers)<form method="POST" action="{{ route('admin.users.unrestrict', $u) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary px-3">رفع التقييد</button></form>@endif
                </div>
            @endforeach
            <hr class="border-secondary">
        @endif

        @forelse ($absentUsers as $r)
            @php $u = $r['user']; @endphp
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <a href="{{ route('admin.users.show', $u) }}" class="text-decoration-none flex-grow-1">{{ $u->name }}</a>
                <span class="small text-secondary">غاب {{ $r['missed'] }} من {{ $r['eligible'] }} · آخر 90 يومًا: {{ $r['recent_missed'] }}</span>
                @if ($r['recent_missed'] >= 3) <span class="liqa-badge liqa-badge--red">يحتاج قرارًا</span> @endif
            </div>
        @empty
            <p class="text-secondary mb-0">لا غيابات مسجّلة. ✓</p>
        @endforelse
    </div>
@endsection
