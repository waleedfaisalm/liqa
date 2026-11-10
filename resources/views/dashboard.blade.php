@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
    @php
        $user = auth()->user();
        $since = $user->created_at->locale('ar')->translatedFormat('j F Y');
    @endphp

    {{-- ============ كارت الترحيب ============ --}}
    <section class="dash-hero mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="eyebrow mb-3">لوحة التحكم</span>
                <h1 class="display-6 liqa-title mb-2">
                    أهلاً، <span class="text-gradient">{{ $user->name }}</span>
                </h1>
                <p class="text-secondary mb-4">تصفّح الأحداث القادمة، أو انشر لقاءك وابدأ بالتعرّف على متخصصين من مجالك.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('events.index') }}" class="btn btn-liqa px-4">
                        تصفّح الأحداث
                    </a>
                    <a href="{{ route('events.create') }}" class="btn btn-liqa-outline px-4 d-flex align-items-center">+ انشر حدث</a>
                    <a href="{{ route('profile') }}" class="btn btn-liqa-outline px-4 d-flex align-items-center gap-2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6"/></svg>
                        أكمل ملفك الشخصي
                    </a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="dash-profile text-center">
                    <div class="mb-3"><x-avatar :user="$user" :size="76" /></div>
                    <div class="fw-bold">{{ $user->name }}</div>
                    <div class="small text-secondary text-break" dir="ltr">{{ $user->email }}</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ ابدأ هنا ============ --}}
    @php
        $doneCount = collect($steps)->where('done', true)->count();
        $total = count($steps);
        $pct = (int) round($doneCount / $total * 100);
    @endphp
    @if ($doneCount < $total)
        <section class="start-panel mb-4" id="startPanel" data-key="liqa-start-{{ $user->id }}">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h4 liqa-title mb-1">ابدأ هنا</h2>
                    <p class="text-secondary small mb-0">خطوات قصيرة تساعدك على الاستفادة من المنصة منذ يومك الأول.</p>
                </div>
                <button type="button" class="btn btn-sm btn-liqa-outline flex-shrink-0" id="startDismiss">إخفاء</button>
            </div>
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="progress flex-grow-1 start-progress" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: {{ $pct }}%"></div>
                </div>
                <span class="small fw-bold text-nowrap">{{ $doneCount }} من {{ $total }}</span>
            </div>
            <ul class="start-steps list-unstyled mb-0">
                @foreach ($steps as $i => $st)
                    <li class="start-step {{ $st['done'] ? 'is-done' : '' }}">
                        <span class="start-step__mark">@if ($st['done']) ✓ @else {{ $i + 1 }} @endif</span>
                        <span class="start-step__body">
                            <span class="start-step__title">{{ $st['title'] }}</span>
                            <span class="start-step__text">{{ $st['text'] }}</span>
                        </span>
                        @unless ($st['done'])
                            <a href="{{ $st['url'] }}" class="btn btn-sm btn-liqa flex-shrink-0">{{ $st['cta'] }}</a>
                        @endunless
                    </li>
                @endforeach
            </ul>
        </section>
        <script>
            (function () {
                var el = document.getElementById('startPanel'), k = el.dataset.key;
                try { if (localStorage.getItem(k) === '1') el.hidden = true; } catch (e) {}
                document.getElementById('startDismiss').addEventListener('click', function () {
                    el.hidden = true;
                    try { localStorage.setItem(k, '1'); } catch (e) {}
                });
            })();
        </script>
    @endif

    {{-- ============ أرقام سريعة ============ --}}
    <section class="row g-3 mb-4">
        <div class="col-md-4">
            <a href="{{ route('events.mine') }}" class="liqa-card h-100 d-block text-decoration-none">
                <div class="small text-secondary mb-1">أحداث قادمة تنظّمها</div>
                <div class="display-6 fw-bold text-white">{{ $myHosted }}</div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('events.mine') }}" class="liqa-card h-100 d-block text-decoration-none">
                <div class="small text-secondary mb-1">أحداث قادمة تم قبولك فيها</div>
                <div class="display-6 fw-bold text-white">{{ $myJoined }}</div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('events.mine') }}" class="liqa-card h-100 d-block text-decoration-none">
                <div class="small text-secondary mb-1">طلبات انضمام بانتظار ردّك</div>
                <div class="display-6 fw-bold text-white">{{ $pending }}</div>
            </a>
        </div>
    </section>

    {{-- ============ أحداث قريبة ============ --}}
    <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 liqa-title mb-0">أحداث قريبة</h2>
            <a href="{{ route('events.index') }}" class="small">عرض الكل</a>
        </div>
        @if ($upcoming->isEmpty())
            <div class="liqa-card text-center py-4">
                <p class="text-secondary mb-3">لا توجد أحداث قادمة من منظّمين آخرين حاليًا.</p>
                <a href="{{ route('events.create') }}" class="btn btn-liqa px-4">انشر حدث</a>
            </div>
        @else
            <div class="row g-3">
                @foreach ($upcoming as $event)
                    <div class="col-md-6 col-lg-4"><x-event-card :event="$event" :user="$user" /></div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
