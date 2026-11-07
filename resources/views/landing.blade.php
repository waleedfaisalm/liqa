@extends(auth()->check() ? 'layouts.app' : 'layouts.site')

@section('title', 'لقاء — منصة لقاءات المستقلين المهنية')
@section('main_class', 'landing-main')

@section('content')
    @php $member = auth()->check(); $L = \App\Support\Catalog::landing(); @endphp

    {{-- ============ الواجهة الرئيسية ============ --}}
    <section class="container pt-4 pt-lg-5">
        <div class="form-card hero-card">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <span class="eyebrow mb-3"><span class="dot"></span>{{ $L['hero']['eyebrow'] }}</span>
                    <h1 class="display-5 liqa-title mb-3">
                        {{ $L['hero']['title'] }}<br>
                        <span class="text-gradient">{{ $L['hero']['title_accent'] }}</span>
                    </h1>
                    <p class="lead text-secondary mb-4">
                        {{ $L['hero']['lead'] }}
                    </p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @if ($member)
                            <a href="{{ route('events.index') }}" class="btn btn-liqa px-4 d-flex align-items-center gap-2">تصفّح الأحداث <x-icon name="calendar" :size="18" /></a>
                            <a href="{{ route('events.create') }}" class="btn btn-liqa-outline px-4 d-flex align-items-center gap-2"><x-icon name="plus" :size="18" /> انشر حدثًا</a>
                        @else
                            <a href="{{ route('register') }}" class="btn btn-liqa px-4 d-flex align-items-center gap-2">ابدأ مجانًا <x-icon name="rocket" :size="18" /></a>
                            <a href="#how" class="btn btn-liqa-outline px-4 d-flex align-items-center gap-2"><x-icon name="play" :size="18" /> كيف تعمل المنصة؟</a>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-3 small hint">
                        <span class="d-flex align-items-center gap-1 text-success"><x-icon name="check" :size="16" /> {{ $L['hero']['hint_1'] }}</span>
                        <span class="d-flex align-items-center gap-1 text-success"><x-icon name="check" :size="16" /> {{ $L['hero']['hint_2'] }}</span>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="float-stack">
                        @php
                            $grads = ['linear-gradient(135deg,#a855f7,#6366f1)', 'linear-gradient(135deg,#fb923c,#db2777)', 'linear-gradient(135deg,#38bdf8,#6366f1)'];
                            if ($upcoming->isNotEmpty()) {
                                $floats = $upcoming->map(fn ($e) => [
                                    'title' => $e->title,
                                    'sub' => $e->starts_at->translatedFormat('l j F').' · '.($e->mode === 'online' ? 'عن بُعد' : $e->city),
                                    'count' => $e->seatsTaken().' / '.$e->capacity.' مشارك',
                                    'badge' => $e->isFull() ? ['مكتمل العدد', ''] : [$e->seatsLeft().' مقاعد متاحة', 'liqa-badge--green'],
                                ])->values()->all();
                            } else {
                                $floats = [
                                    ['title' => 'لقاء مطوّري الواجهات', 'sub' => 'السبت · الرياض', 'count' => '8 / 15 مشاركًا', 'badge' => ['7 مقاعد متاحة', 'liqa-badge--green']],
                                    ['title' => 'جلسة المصممين', 'sub' => 'الجمعة · جدة', 'count' => '11 / 12 مشاركًا', 'badge' => ['مقعد واحد متاح', '']],
                                    ['title' => 'لقاء كتّاب المحتوى', 'sub' => 'الأحد · عن بُعد', 'count' => '0 / 20 مشارك', 'badge' => ['لقاء جديد', 'liqa-badge--sky']],
                                ];
                            }
                        @endphp
                        @foreach ($floats as $i => $f)
                            <div class="float-card float-card--{{ $i + 1 }}">
                                <div class="float-card__head">
                                    <span class="float-card__dot" style="background: {{ $grads[$i] }}"></span>
                                    <span class="min-w-0">
                                        <span class="float-card__title">{{ $f['title'] }}</span>
                                        <span class="float-card__sub">{{ $f['sub'] }}</span>
                                    </span>
                                </div>
                                <div class="float-card__foot">
                                    <span class="small hint d-flex align-items-center gap-1"><x-icon name="users" :size="14" /> {{ $f['count'] }}</span>
                                    <span class="liqa-badge {{ $f['badge'][1] }}">{{ $f['badge'][0] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($upcoming->isEmpty())
                        <p class="small text-center hint mt-3 mb-0">أمثلة توضيحية لشكل اللقاءات على المنصة</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ============ المنصة بالأرقام ============ --}}
    @if ($showStats)
        <section class="container landing-section">
            <div class="text-center mb-4">
                <span class="eyebrow eyebrow--green mb-3"><span class="dot"></span>{{ $L['stats']['eyebrow'] }}</span>
                <h2 class="display-6 liqa-title mb-0">{{ $L['stats']['title'] }} <span class="text-gradient">{{ $L['stats']['title_accent'] }}</span></h2>
            </div>
            <div class="row g-3">
                @foreach ([['members', 'عضو مستقل', 'users'], ['events', 'لقاء منشور', 'calendar'], ['cities', 'مدينة', 'pin'], ['joined', 'مشاركة مؤكدة', 'check']] as [$k, $label, $icon])
                    <div class="col-6 col-lg-3">
                        <div class="liqa-card stat-card h-100 text-center p-3 p-md-4">
                            <span class="feature-icon mb-2"><x-icon :name="$icon" :size="22" /></span>
                            <div class="stat-card__num text-gradient">{{ number_format($stats[$k]) }}</div>
                            <div class="small text-secondary">{{ $label }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============ المشكلة ============ --}}
    <section class="landing-band"><div class="container landing-section">
        <div class="text-center mb-4">
            <span class="eyebrow eyebrow--pink mb-3"><span class="dot"></span>{{ $L['problem']['eyebrow'] }}</span>
            <h2 class="display-6 liqa-title mb-2">{{ $L['problem']['title'] }} <span class="text-gradient">{{ $L['problem']['title_accent'] }}</span></h2>
            <p class="hint mb-0">{{ $L['problem']['subtitle'] }}</p>
        </div>

        <div class="row g-3">
            @foreach ([['question', ''], ['trend', 'feature-icon--orange'], ['chat', 'feature-icon--pink']] as $i => [$icon, $tone])
                @php $title = $L['problem']['cards'][$i]['title']; $text = $L['problem']['cards'][$i]['text']; @endphp
                <div class="col-md-4">
                    <div class="liqa-card h-100 p-3 p-md-4">
                        <span class="feature-icon {{ $tone }} mb-3"><x-icon :name="$icon" :size="22" /></span>
                        <h3 class="h5 liqa-title">{{ $title }}</h3>
                        <p class="text-secondary small mb-0">{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div></section>

    {{-- ============ الحل ============ --}}
    <section class="container landing-section" id="how">
        <div class="text-center mb-4">
            <span class="eyebrow mb-3"><span class="dot"></span>{{ $L['how']['eyebrow'] }}</span>
            <h2 class="display-6 liqa-title mb-2">{{ $L['how']['title'] }} <span class="text-gradient">{{ $L['how']['title_accent'] }}</span></h2>
            <p class="hint mb-0">{{ $L['how']['subtitle'] }}</p>
        </div>

        <div class="row g-3">
            @foreach ([['١', 'user-plus', 'feature-icon--sky'], ['٢', 'search', ''], ['٣', 'thumb', ''], ['٤', 'coffee', 'feature-icon--pink']] as $i => [$num, $icon, $tone])
                @php $title = $L['how']['cards'][$i]['title']; $text = $L['how']['cards'][$i]['text']; @endphp
                <div class="col-sm-6 col-lg-3">
                    <div class="liqa-card step-card h-100 p-3 p-md-4">
                        <span class="step-card__num" aria-hidden="true">{{ $num }}</span>
                        <span class="feature-icon {{ $tone }} mb-3"><x-icon :name="$icon" :size="22" /></span>
                        <h3 class="h5 liqa-title">{{ $title }}</h3>
                        <p class="text-secondary small mb-0">{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ لمن المنصة ============ --}}
    <section class="landing-band"><div class="container landing-section">
        <div class="text-center mb-4">
            <span class="eyebrow eyebrow--pink mb-3"><span class="dot"></span>{{ $L['audience']['eyebrow'] }}</span>
            <h2 class="display-6 liqa-title mb-2">{{ $L['audience']['title'] }} <span class="text-gradient">{{ $L['audience']['title_accent'] }}</span></h2>
            <p class="hint mb-0">{{ $L['audience']['subtitle'] }}</p>
        </div>
        <div class="row g-3">
            @foreach ([['bolt', ''], ['star', 'feature-icon--pink'], ['edit', 'feature-icon--sky'], ['trend', 'feature-icon--orange'], ['coffee', 'feature-icon--green'], ['users', '']] as $i => [$icon, $tone])
                @php $card = $L['audience']['cards'][$i]; @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="liqa-card d-flex gap-3 align-items-start p-3 p-md-4 h-100">
                        <span class="feature-icon {{ $tone }}"><x-icon :name="$icon" :size="22" /></span>
                        <span>
                            <h3 class="h6 liqa-title mb-1">{{ $card['title'] }}</h3>
                            <span class="d-block text-secondary small">{{ $card['text'] }}</span>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div></section>

    {{-- ============ لماذا لقاء ============ --}}
    <section class="container landing-section">
        <div class="text-center mb-4">
            <span class="eyebrow eyebrow--sky mb-3"><span class="dot"></span>{{ $L['why']['eyebrow'] }}</span>
            <h2 class="display-6 liqa-title mb-2">{{ $L['why']['title'] }} <span class="text-gradient">{{ $L['why']['title_accent'] }}</span></h2>
        </div>

        <div class="row g-3">
            @foreach ([['shield', ''], ['pin', 'feature-icon--green'], ['tag', 'feature-icon--sky'], ['bolt', 'feature-icon--orange']] as $i => [$icon, $tone])
                @php $title = $L['why']['cards'][$i]['title']; $text = $L['why']['cards'][$i]['text']; @endphp
                <div class="col-md-6">
                    <div class="liqa-card d-flex gap-3 align-items-start p-3 p-md-4 h-100">
                        <span class="feature-icon {{ $tone }}"><x-icon :name="$icon" :size="22" /></span>
                        <span>
                            <h3 class="h6 liqa-title mb-1">{{ $title }}</h3>
                            <span class="d-block text-secondary small">{{ $text }}</span>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ اللقاءات القادمة ============ --}}
    @if ($upcoming->isNotEmpty())
        <section class="container landing-section">
            <div class="text-center mb-4">
                <span class="eyebrow mb-3"><span class="dot"></span>{{ $L['upcoming']['eyebrow'] }}</span>
                <h2 class="display-6 liqa-title mb-2">{{ $L['upcoming']['title'] }} <span class="text-gradient">{{ $L['upcoming']['title_accent'] }}</span></h2>
                <p class="hint mb-0">{{ $L['upcoming']['subtitle'] }}</p>
            </div>
            <div class="row g-3">
                @foreach ($upcoming as $event)
                    <div class="col-md-6 col-lg-4"><x-event-card :event="$event" :user="auth()->user()" /></div>
                @endforeach
            </div>
            <div class="text-center mt-4">
                <a href="{{ route('events.index') }}" class="btn btn-liqa-outline px-4">عرض جميع اللقاءات</a>
            </div>
        </section>
    @endif

    {{-- ============ الأسئلة الشائعة ============ --}}
    <section class="container landing-section" id="faq">
        <div class="text-center mb-4">
            <span class="eyebrow eyebrow--sky mb-3"><span class="dot"></span>{{ $L['faq']['eyebrow'] }}</span>
            <h2 class="display-6 liqa-title mb-0">{{ $L['faq']['title'] }} <span class="text-gradient">{{ $L['faq']['title_accent'] }}</span></h2>
        </div>
        <div class="accordion faq-list mx-auto" id="faqAccordion">
            @foreach ($L['faq']['items'] as $i => $item)
                <div class="accordion-item liqa-card faq-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq-{{ $i }}" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="faq-{{ $i }}">{{ $item['title'] }}</button>
                    </h3>
                    <div id="faq-{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-secondary small">{{ $item['text'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ دعوة أخيرة ============ --}}
    <section class="container landing-section pb-5">
        <div class="cta-box text-center">
            <h2 class="display-6 liqa-title mb-2">
                @php $C = $member ? $L['cta_member'] : $L['cta_guest']; @endphp
                {{ $C['title'] }} <span class="text-gradient">{{ $C['title_accent'] }}</span>
            </h2>
            <p class="hint mb-4">
                {{ $C['text'] }}
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                @if ($member)
                    <a href="{{ route('events.index') }}" class="btn btn-liqa px-4">تصفّح الأحداث</a>
                    <a href="{{ route('events.create') }}" class="btn btn-liqa-outline px-4 d-flex align-items-center">انشر حدثًا</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-liqa px-4 d-flex align-items-center gap-2">أنشئ حسابك مجانًا <x-icon name="user-plus" :size="18" /></a>
                    <a href="{{ route('login') }}" class="btn btn-liqa-outline px-4 d-flex align-items-center">لديك حساب؟ سجّل دخولك</a>
                @endif
            </div>
        </div>
    </section>
@endsection
