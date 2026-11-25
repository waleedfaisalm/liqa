@extends('layouts.app')
@section('title', $isMe ? __('ملفي الشخصي') : $profile->name)
@section('content')
    @php
        $since = $profile->created_at->translatedFormat('F Y');
        $maxBar = max(1, $distribution->max());
    @endphp

    <div class="row g-4">
        {{-- ============ بطاقة الشخص ============ --}}
        <div class="col-lg-4">
            <div class="form-card text-center">
                <div class="mb-3"><x-avatar :user="$profile" :size="96" /></div>
                <h1 class="h3 liqa-title mb-2 d-inline-flex align-items-center justify-content-center gap-2">{{ $profile->name }} @if ($profile->isVerified())<x-verified :size="24" />@endif</h1>
                @if ($profile->specialty)
                    <span class="liqa-badge mb-3">{{ __($profile->specialty) }}</span>
                @endif

                @if ($profile->city)
                    <div class="small text-secondary mb-2">📍 {{ $profile->city }}</div>
                @elseif ($isMe)
                    <div class="small mb-2"><a href="{{ route('settings') }}" class="text-decoration-none">{{ __('أضف مدينتك لنقترح عليك لقاءات قريبة') }}</a></div>
                @endif

                @php $rel = \App\Support\Reliability::stats($profile); @endphp
                @if ($isMe && $rel['eligible'] > 0)
                    <div class="small mb-3 p-2 rounded" style="background: rgba(127,127,127,.12)">
                        {{ __('التزامك بالحضور: :r% (:a من :e)', ['r' => $rel['rate'], 'a' => $rel['attended'], 'e' => $rel['eligible']]) }}
                        @if ($rel['recent_missed'] > 0) <div class="text-warning-emphasis">{{ __('غيابات آخر 90 يومًا: :n', ['n' => $rel['recent_missed']]) }}</div> @endif
                        @if ($profile->isJoinRestricted()) <div class="text-danger">{{ __('انضمامك مقيّد حتى :d', ['d' => $profile->join_restricted_until->translatedFormat('j F')]) }}</div> @endif
                    </div>
                @elseif (! $isMe && $rel['eligible'] >= 3 && $rel['rate'] >= 90)
                    <span class="liqa-badge liqa-badge--green mb-3">{{ __('ملتزم بالحضور') }}</span>
                @endif

                @if ($profile->bio)
                    <p class="text-secondary" style="white-space: pre-line">{{ $profile->bio }}</p>
                @else
                    <p class="text-secondary">{{ $isMe ? __('لم تضف نبذة بعد. يمكنك إضافتها من صفحة التعديل.') : __('لا توجد نبذة.') }}</p>
                @endif

                @if (count($profile->workLinks()))
                    <div class="profile-links">
                        @foreach ($profile->workLinks() as [$label, $url])
                            <a href="{{ $url }}" target="_blank" rel="noopener nofollow ugc" class="profile-link"><x-icon name="link" :size="14" /> {{ __($label) }}</a>
                        @endforeach
                    </div>
                @endif

                <div class="profile-score">
                    <x-stars :value="$average ?? 0" :size="26" />
                    <div class="mt-2">
                        <span class="fs-3 fw-bold text-white">{{ $average !== null ? $average : '—' }}</span>
                        <span class="text-secondary"> / 5</span>
                    </div>
                    <div class="small text-secondary">{{ __('من :v1 تقييم', ['v1' => $ratingsCount]) }}</div>
                </div>

                @if (count($badges))
                    <div class="badge-list justify-content-center mb-3">
                        @foreach ($badges as $b)
                            <span class="badge-pill badge-pill--t{{ $b['tier'] }}" title="{{ $b['desc'] }}">{{ $b['icon'] }} {{ $b['label'] }}</span>
                        @endforeach
                    </div>
                @elseif ($isMe)
                    <p class="small hint">{{ __('ستظهر شاراتك هنا عند مشاركتك في اللقاءات أو تنظيمها.') }}</p>
                @endif

                <div class="small hint d-flex align-items-center justify-content-center gap-2 mb-3">
                    <x-icon name="calendar" :size="15" /> {{ __('عضو منذ :v1', ['v1' => $since]) }}
                </div>

                @if ($isMe)
                    <a href="{{ route('settings') }}" class="btn btn-liqa-outline w-100 d-flex align-items-center justify-content-center gap-2">
                        <x-icon name="edit" :size="17" /> {{ __('عدّل ملفي الشخصي') }}
                    </a>
                    <a href="{{ route('contacts.index') }}" class="btn btn-liqa-outline w-100 d-flex align-items-center justify-content-center gap-2 mt-2">
                        <x-icon name="users" :size="17" /> {{ __('شبكة التواصل') }}@if ($pendingContacts > 0) <span class="nav-badge">{{ $pendingContacts }}</span> @endif
                    </a>
                @else
                    <a href="{{ route('reports.create', ['type' => 'user', 'id' => $profile->id]) }}" class="small text-secondary text-decoration-none">{{ __('⚑ إبلاغ عن هذا المستخدم') }}</a>
                @endif
            </div>
        </div>

        {{-- ============ المحتوى ============ --}}
        <div class="col-lg-8">
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3"><div class="stat-tile"><div class="stat-tile__num">{{ $ratingsCount }}</div><div class="stat-tile__label">{{ __('تقييم') }}</div></div></div>
                <div class="col-6 col-md-3"><div class="stat-tile"><div class="stat-tile__num">{{ $hostedCount }}</div><div class="stat-tile__label">{{ __('حدث نشره') }}</div></div></div>
                <div class="col-6 col-md-3"><div class="stat-tile"><div class="stat-tile__num">{{ $hostedReach }}</div><div class="stat-tile__label">{{ __('حضور في لقاءاته') }}</div></div></div>
                <div class="col-6 col-md-3"><div class="stat-tile"><div class="stat-tile__num">{{ $attendedReal }}</div><div class="stat-tile__label">{{ __('لقاء حضره فعليًا') }}</div></div></div>
            </div>

            @if ($topTags->isNotEmpty())
                <div class="liqa-card p-3 mb-3">
                    <div class="fw-bold text-white mb-3"># {{ $isMe ? __('كيف يصفك الناس') : __('كيف يصفه الناس') }}</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($topTags as $tag => $count)
                            <span class="liqa-chip">{{ $count }}× {{ __($tag) }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="tab-pills mb-3" role="tablist">
                <a href="{{ request()->url() }}" class="tab-pill {{ $tab === 'events' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $tab === 'events' ? 'true' : 'false' }}">
                    <x-icon name="calendar" :size="18" /> {{ __('الأحداث') }} <span class="count">{{ $upcoming->count() + $past->count() }}</span>
                </a>
                <a href="{{ request()->url() }}?tab=ratings" class="tab-pill {{ $tab === 'ratings' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $tab === 'ratings' ? 'true' : 'false' }}">
                    <x-icon name="star" :size="18" /> {{ __('التقييمات') }} <span class="count">{{ $ratingsCount }}</span>
                </a>
            </div>

            @if ($tab === 'events')
                <h2 class="h6 liqa-title mb-3 d-flex align-items-center gap-2 text-info"><x-icon name="calendar" :size="17" /> {{ __('أحداث قادمة (:v1)', ['v1' => $upcoming->count()]) }}</h2>
                @if ($upcoming->isEmpty())
                    <div class="liqa-card text-center text-secondary mb-4">{{ __('لا توجد أحداث قادمة.') }}</div>
                @else
                    <div class="row g-3 mb-4">
                        @foreach ($upcoming as $ev)
                            <div class="col-md-6"><x-event-tile :event="$ev" /></div>
                        @endforeach
                    </div>
                @endif

                <h2 class="h6 liqa-title mb-3 d-flex align-items-center gap-2 text-info"><x-icon name="clock" :size="17" /> {{ __('أحداث سابقة (:v1)', ['v1' => $past->count()]) }}</h2>
                @if ($past->isEmpty())
                    <div class="liqa-card text-center text-secondary">{{ __('لا توجد أحداث سابقة.') }}</div>
                @else
                    <div class="row g-3">
                        @foreach ($past as $ev)
                            <div class="col-md-6"><x-event-tile :event="$ev" /></div>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="liqa-card p-3 p-md-4 mb-3">
                    <h2 class="h6 liqa-title mb-3 d-flex align-items-center gap-2"><x-icon name="chart" :size="18" /> {{ __('توزيع التقييمات') }}</h2>
                    @foreach ($distribution as $n => $count)
                        <div class="rate-bar">
                            <span class="rate-bar__label">{{ $n }} <span class="text-warning">★</span></span>
                            <span class="rate-bar__track"><span style="width: {{ round($count / $maxBar * 100) }}%"></span></span>
                            <span class="rate-bar__count">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>

                @if (\App\Support\Ai::enabled() && $ratingsCount >= 3)
                    @php $aiSum = \App\Support\Ai::cachedRatingsSummary($profile, $ratingsCount); @endphp
                    <div class="liqa-card p-3 p-md-4 mb-3">
                        <h2 class="h6 liqa-title mb-2">✨ {{ __('ملخص التقييمات') }}</h2>
                        <p class="small text-secondary mb-2" id="ai-sum" style="white-space: pre-line">{{ $aiSum }}</p>
                        @unless ($aiSum)
                            <button type="button" class="btn btn-sm btn-liqa-outline" id="ai-sum-btn">{{ __('لخّص التقييمات') }}</button>
                            <script>
                                document.getElementById('ai-sum-btn').addEventListener('click', function () {
                                    var b = this, o = document.getElementById('ai-sum'); b.disabled = true; o.textContent = {{ Js::from(__('جارٍ التلخيص...')) }};
                                    fetch({{ Js::from(route('ai.ratings', $profile)) }}, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': {{ Js::from(csrf_token()) }} } })
                                        .then(function (r) { return r.json(); })
                                        .then(function (j) { o.textContent = j.text || j.message || ''; if (j.ok) b.remove(); else b.disabled = false; })
                                        .catch(function () { o.textContent = {{ Js::from(__('تعذّر الاتصال.')) }}; b.disabled = false; });
                                });
                            </script>
                        @endunless
                        <div class="hint mt-2">{{ __('ملخص آلي لتقييمات الأعضاء، وقد لا يعكس كل التفاصيل.') }}</div>
                    </div>
                @endif

                @forelse ($ratings as $rating)
                    <div class="request-row d-block mb-2">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <a href="{{ route('users.show', $rating->rater) }}" class="d-flex align-items-center gap-2 text-decoration-none me-auto">
                                <x-avatar :user="$rating->rater" :size="38" />
                                <span>
                                    <span class="d-block fw-bold text-white">{{ $rating->rater->name }}</span>
                                    <span class="d-block small hint">{{ __('في :v1 · :v2', ['v1' => $rating->event->title, 'v2' => $rating->event->starts_at->translatedFormat('j F Y')]) }}</span>
                                </span>
                            </a>
                            <x-stars :value="$rating->stars" :size="18" />
                        </div>
                        @if ($rating->tags)
                            <div class="d-flex flex-wrap gap-1 mb-1">
                                @foreach ($rating->tags as $tag) <span class="liqa-badge">{{ __($tag) }}</span> @endforeach
                            </div>
                        @endif
                        @if ($rating->comment)
                            <p class="small text-secondary mb-0">«{{ $rating->comment }}»</p>
                        @endif
                    </div>
                @empty
                    <div class="liqa-card text-center text-secondary">{{ __('لا توجد تقييمات بعد.') }}</div>
                @endforelse
            @endif
        </div>
    </div>
@endsection
