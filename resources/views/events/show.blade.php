@extends(auth()->check() ? 'layouts.app' : 'layouts.site')
@section('title', $event->title)
@push('head')
    @php
        $isOnlineEvent = $event->mode === 'online';
        $ld = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event->title,
            'description' => \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', (string) $event->description)), 300),
            'startDate' => $event->starts_at->toAtomString(),
            'endDate' => $event->endsAt()->toAtomString(),
            'eventStatus' => $event->isCancelled() ? 'https://schema.org/EventCancelled' : 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => $isOnlineEvent ? 'https://schema.org/OnlineEventAttendanceMode' : 'https://schema.org/OfflineEventAttendanceMode',
            'location' => $isOnlineEvent
                ? ['@type' => 'VirtualLocation', 'url' => route('events.show', $event)]
                : ['@type' => 'Place', 'name' => $event->location ?: ($event->city ?: __('لقاء حضوري')), 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $event->city, 'addressCountry' => $event->country]],
            'organizer' => ['@type' => 'Person', 'name' => $event->host->name ?? __('لقاء')],
            'isAccessibleForFree' => true,
            'url' => route('events.show', $event),
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<div @guest class="container py-4 py-lg-5" @endguest>
    @php
        $user = auth()->user();
        $started = $event->starts_at->isPast();
        $ended = $event->hasEnded();
        $open = ! $event->isCancelled() && ! $started;
        $statusLabels = ['pending' => __('قيد المراجعة'), 'accepted' => __('مقبول'), 'rejected' => __('مرفوض'), 'waitlisted' => __('قائمة الانتظار')];
        $statusClasses = ['pending' => 'liqa-badge--pink', 'accepted' => 'liqa-badge--green', 'rejected' => 'liqa-badge--red', 'waitlisted' => 'liqa-badge--sky'];
        $taken = $event->accepted_requests_count;
        $left = max(0, $event->capacity - $taken);
        $percent = $event->capacity ? min(100, round($taken / $event->capacity * 100)) : 0;
    @endphp

    <a href="{{ route('events.index') }}" class="small text-secondary text-decoration-none">{{ __('← كل الأحداث') }}</a>

    <div class="row g-4 mt-1">
        {{-- ============ الجزء الرئيسي ============ --}}
        <div class="col-lg-8">
            @php [$c1, $c2, $coverIcon] = $event->coverTheme(); @endphp
            <div class="event-cover mb-3" style="--c1: {{ $c1 }}; --c2: {{ $c2 }}" aria-hidden="true">
                <span class="event-cover__icon"><x-icon :name="$coverIcon" :size="150" /></span>
                <span class="event-cover__type">{{ $event->typeLabel() }}</span>
                <span class="event-cover__date">
                    <span class="event-cover__day">{{ $event->starts_at->translatedFormat('j') }}</span>
                    <span class="event-cover__month">{{ $event->starts_at->translatedFormat('M') }}</span>
                </span>
            </div>
            <div class="form-card">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="liqa-badge {{ $event->typeBadgeClass() }}">{{ $event->typeLabel() }}</span>
                    <span class="liqa-badge liqa-badge--green">{{ $event->modeLabel() }}</span>
                    @if ($event->isCancelled())
                        <span class="liqa-badge liqa-badge--red">{{ __('ملغي') }}</span>
                    @elseif ($ended)
                        <span class="liqa-badge">{{ __('انتهى') }}</span>
                    @else
                        <span class="liqa-badge liqa-badge--pink">{{ __('قادم') }}</span>
                    @endif
                </div>

                <h1 class="display-6 liqa-title mb-3">{{ $event->title }}</h1>

                <ul class="meta-list meta-list--inline mb-4">
                    <li><x-icon name="calendar" :size="16" /> {{ $event->starts_at->translatedFormat('l j F Y') }}</li>
                    <li><x-icon name="clock" :size="16" /> {{ $event->starts_at->translatedFormat('g:i A') }} · {{ $event->durationLabel() }}</li>
                    <li><x-icon name="user" :size="16" /> <a href="{{ $isHost ? route('profile') : route('users.show', $event->host) }}" class="text-decoration-none">{{ $event->host->name }}</a></li>
                </ul>

                @if ($event->isCancelled())
                    <div class="liqa-alert liqa-alert--err mb-4" role="alert">{{ __('تم إلغاء هذا الحدث من قِبل المنظّم.') }}</div>
                @endif

                <div class="fw-bold text-white mb-2">{{ __('عن اللقاء') }}</div>
                <p class="text-secondary mb-4" style="white-space: pre-line">{{ $event->description }}</p>

                @if ($event->speaker_name)
                    <div class="speaker-card mb-4">
                        <span class="speaker-card__avatar">{{ \Illuminate\Support\Str::substr($event->speaker_name, 0, 1) }}</span>
                        <span class="min-w-0">
                            <span class="d-block small text-secondary">{{ __('المتحدث') }}</span>
                            <span class="d-block fw-bold text-white">{{ $event->speaker_name }}</span>
                            @if ($event->speaker_bio) <span class="d-block small text-secondary">{{ $event->speaker_bio }}</span> @endif
                        </span>
                    </div>
                @endif

                @php $timeline = $event->agendaTimeline(); @endphp
                @if ($timeline)
                    <div class="fw-bold text-white mb-3">{{ __('جدول اللقاء') }}</div>
                    <ol class="agenda mb-4">
                        @foreach ($timeline as $row)
                            <li class="agenda__item">
                                <span class="agenda__time">{{ $row['time'] }}</span>
                                <span class="agenda__body">
                                    <span class="d-block fw-bold text-white">{{ $row['title'] }}</span>
                                    <span class="d-block small hint">{{ __(':n دقيقة', ['n' => $row['minutes']]) }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="info-tile">
                            <span class="info-tile__icon"><x-icon name="pin" :size="20" /></span>
                            <span class="min-w-0">
                                <span class="d-block small text-secondary">{{ $event->mode === 'online' ? __('الرابط أو المنصة') : __('المكان') }}</span>
                                <span class="d-block fw-bold text-white text-break">
                                    @if ($event->mode === 'online' && \Illuminate\Support\Str::startsWith($event->location, ['http://', 'https://']))
                                        <a href="{{ $event->location }}" target="_blank" rel="noopener noreferrer nofollow">{{ $event->location }}</a>
                                    @else
                                        {{ $event->location }}
                                    @endif
                                </span>
                                @if ($event->city || $event->country) <span class="d-block small hint">{{ implode(__('، '), array_filter([$event->city, $event->country])) }}</span> @endif
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-tile">
                            <span class="info-tile__icon"><x-icon name="users" :size="20" /></span>
                            <span>
                                <span class="d-block small text-secondary">{{ __('المشاركون') }}</span>
                                <span class="d-block fw-bold text-white"><bdi dir="ltr">{{ $taken }} / {{ $event->capacity }}</bdi></span>
                                <span class="d-block small hint">{{ $left > 0 ? $left.__(' مقاعد متاحة') : __('اكتمل العدد') }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- زر الإجراء على الجوال: مباشرة بعد التفاصيل --}}
            <div class="d-lg-none mt-3">
                @include('events._action', ['event' => $event, 'isHost' => $isHost, 'open' => $open, 'myRequest' => $myRequest, 'started' => $started, 'waitlistPosition' => $waitlistPosition ?? null, 'percent' => $percent, 'statusLabels' => $statusLabels, 'statusClasses' => $statusClasses])
            </div>

            {{-- ============ التعليقات والأسئلة ============ --}}
            <div class="liqa-card p-3 p-md-4 mt-3" id="comments">
                <h2 class="h5 liqa-title mb-3 d-flex align-items-center gap-2"><x-icon name="chat" :size="20" /> {{ __('الأسئلة والتعليقات (:v1)', ['v1' => $comments->count()]) }}</h2>

                @if ($comments->isEmpty())
                    <p class="text-secondary mb-3">{{ __('لا توجد تعليقات بعد. اطرح سؤالك للمنظّم قبل أن تقدّم طلبك.') }}</p>
                @else
                    <div class="d-grid gap-2 mb-3">
                        @foreach ($comments as $comment)
                            <div class="request-row d-block">
                                <div class="d-flex align-items-start gap-2">
                                    <x-avatar :user="$comment->user" :size="36" />
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <a href="{{ auth()->check() ? route('users.show', $comment->user) : route('login') }}" class="fw-bold text-white text-decoration-none">{{ $comment->user->name }}</a>
                                            @if ($comment->user_id === $event->user_id) <span class="liqa-badge">{{ __('المنظّم') }}</span> @endif
                                            <span class="small hint">{{ $comment->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="mb-0 text-break" style="white-space: pre-line">{{ $comment->body }}</p>
                                    </div>
                                    @if (auth()->check() && (auth()->id() === $comment->user_id || $isHost))
                                        <form method="POST" action="{{ route('events.comments.destroy', [$event, $comment]) }}" onsubmit="return confirm('{{ addslashes(__('هل تريد حذف هذا التعليق؟')) }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm text-danger p-1" aria-label="{{ __('حذف التعليق') }}" title="{{ __('حذف') }}">✕</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($event->isCancelled() || $ended)
                    <p class="small hint mb-0">{{ __('التعليقات مغلقة على هذا الحدث.') }}</p>
                @elseif (auth()->check())
                    <form method="POST" action="{{ route('events.comments.store', $event) }}" novalidate class="d-grid gap-2">
                        @csrf
                        <textarea name="body" rows="2" maxlength="500" required
                                  class="form-control @error('body') is-invalid @enderror"
                                  placeholder="{{ $isHost ? __('اكتب ردًا على الأسئلة...') : __('اكتب سؤالك أو تعليقك...') }}">{{ old('body') }}</textarea>
                        @error('body') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <div><button type="submit" class="btn btn-liqa px-4">{{ __('إرسال') }}</button></div>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-liqa-outline px-4">{{ __('سجّل دخولك لإضافة تعليق') }}</a>
                @endif
            </div>

            @if ($isHost && ! $ended && ! $event->isCancelled())
                @include('events._insights', ['event' => $event])
            @endif

            {{-- ============ طلبات الانضمام (المنظّم فقط) ============ --}}
            @if ($isHost)
                <div class="liqa-card p-3 p-md-4 mt-3" id="requests">
                    <h2 class="h5 liqa-title mb-3 d-flex align-items-center gap-2"><x-icon name="users" :size="20" /> {{ __('طلبات الانضمام (:v1)', ['v1' => $requests->count()]) }}</h2>

                    @if ($requests->isEmpty())
                        <p class="text-secondary mb-0">{{ __('لا توجد طلبات حتى الآن.') }}</p>
                    @else
                        <div class="d-grid gap-2">
                            @foreach ($requests as $req)
                                <div class="request-row">
                                    <a href="{{ route('users.show', $req->user) }}" class="d-flex align-items-center gap-2 text-decoration-none me-auto">
                                        <x-avatar :user="$req->user" :size="40" />
                                        <span>
                                            <span class="d-block fw-bold text-white">{{ $req->user->name }}</span>
                                            <span class="d-block small text-secondary">{{ __($req->user->specialty) }}</span>
                                        </span>
                                    </a>

                                    <span class="liqa-badge {{ $statusClasses[$req->status] }}">{{ $statusLabels[$req->status] }}</span>

                                    @if ($open)
                                        <form method="POST" action="{{ route('events.requests.decide', [$event, $req]) }}" class="d-flex gap-2">
                                            @csrf
                                            @method('PATCH')
                                            @if ($req->status !== 'accepted' && $req->status !== 'waitlisted')
                                                <button name="decision" value="accept" class="btn btn-liqa btn-sm px-3" style="min-height:2.25rem">{{ __('قبول') }}</button>
                                            @endif
                                            @if ($req->status !== 'rejected')
                                                <button name="decision" value="reject" class="btn btn-liqa-outline btn-sm px-3" style="min-height:2.25rem">{{ __('رفض') }}</button>
                                            @endif
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- ============ الحضور الفعلي ============ --}}
            @if ($ended && ! $event->isCancelled() && ($isHost || $myRequest))
                <div class="liqa-card p-3 p-md-4 mt-3" id="attendance">
                    <h2 class="h5 liqa-title mb-1 d-flex align-items-center gap-2"><x-icon name="check" :size="20" /> {{ __('الحاضرون فعليًا (:v1)', ['v1' => $attendees->count()]) }}</h2>
                    <p class="small hint mb-3">{{ __('يُحتسب الحضور من مدة البقاء في الغرفة المباشرة (10 دقائق على الأقل).') }}
                        @if ($isHost && $noShows > 0) · {{ __(':n مقبولين لم يحضروا', ['n' => $noShows]) }} @endif</p>
                    @if ($attendees->isEmpty())
                        <p class="text-secondary mb-0">{{ __('لم يُسجَّل حضور عبر الغرفة المباشرة لهذا الحدث.') }}</p>
                    @else
                        <div class="d-grid gap-2">
                            @foreach ($attendees as $a)
                                <div class="request-row">
                                    <a href="{{ route('users.show', $a->user) }}" class="d-flex align-items-center gap-2 me-auto text-decoration-none">
                                        <x-avatar :user="$a->user" :size="38" />
                                        <span class="fw-bold text-white">{{ $a->user->name }}</span>
                                    </a>
                                    @if ($isHost) <span class="small hint">{{ __(':n دقيقة', ['n' => $a->minutes()]) }}</span> @endif
                                    @if ($user && $a->user_id !== $user->id && ! $a->user->suspended_at)
                                        @php $cr = \App\Models\ContactRequest::between($user, $a->user); @endphp
                                        @if (! $cr)
                                            <form method="POST" action="{{ route('contacts.store', $a->user) }}">
                                                @csrf
                                                <button class="btn btn-liqa-outline btn-sm px-3" style="min-height:2.25rem">{{ __('تواصل') }}</button>
                                            </form>
                                        @elseif ($cr->status === 'accepted')
                                            <a href="{{ route('contacts.index') }}" class="liqa-badge liqa-badge--green text-decoration-none">{{ __('في جهات اتصالك') }}</a>
                                        @elseif ($cr->status === 'pending')
                                            <span class="liqa-badge">{{ __('بانتظار الرد') }}</span>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- ============ قيّم المشاركين (بعد انتهاء الحدث) ============ --}}
            @if ($ratable->isNotEmpty())
                <div class="liqa-card p-3 p-md-4 mt-3" id="ratings">
                    <h2 class="h5 liqa-title mb-1 d-flex align-items-center gap-2"><span class="text-warning">★</span> {{ __('قيّم المشاركين') }}</h2>
                    <p class="small hint mb-3">{{ __('انتهى الحدث. قيّم من التقيت بهم لتساعد المجتمع.') }}</p>

                    <div class="d-grid gap-2">
                        @foreach ($ratable as $person)
                            @php $given = $myRatings->get($person->id); @endphp
                            <div class="request-row">
                                <a href="{{ route('users.show', $person) }}" class="d-flex align-items-center gap-2 text-decoration-none me-auto">
                                    <x-avatar :user="$person" :size="40" />
                                    <span>
                                        <span class="d-block fw-bold text-white">{{ $person->name }}</span>
                                        <span class="d-block small text-secondary">{{ $person->id === $event->user_id ? __('المنظّم') : $person->specialty }}</span>
                                    </span>
                                </a>

                                @if ($given)
                                    <x-stars :value="$given->stars" :size="18" />
                                    <span class="liqa-badge liqa-badge--green">{{ __('تم التقييم') }}</span>
                                @else
                                    <a href="{{ route('events.rate.create', [$event, $person]) }}" class="btn btn-liqa btn-sm px-3 d-flex align-items-center gap-1" style="min-height:2.25rem">
                                        {{ __('قيّم') }} <x-icon name="star" :size="15" />
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ============ تقييمات الحدث ============ --}}
            @if ($eventRatings->isNotEmpty())
                <div class="liqa-card p-3 p-md-4 mt-3" @if ($ratable->isEmpty()) id="ratings" @endif>
                    <h2 class="h5 liqa-title mb-3 d-flex align-items-center gap-2"><x-icon name="chat" :size="20" /> {{ __('تقييمات الحدث (:v1)', ['v1' => $eventRatings->count()]) }}</h2>
                    <div class="d-grid gap-2">
                        @foreach ($eventRatings as $rating)
                            <div class="request-row d-block">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                                    <span class="small">
                                        <a href="{{ route('users.show', $rating->rater) }}" class="fw-bold text-white text-decoration-none">{{ $rating->rater->name }}</a>
                                        <span class="text-secondary">{{ __('قيّم') }}</span>
                                        <a href="{{ route('users.show', $rating->ratee) }}" class="fw-bold text-decoration-none">{{ $rating->ratee->name }}</a>
                                    </span>
                                    <x-stars :value="$rating->stars" :size="16" />
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
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- ============ الجانب ============ --}}
        <div class="col-lg-4">
            <div class="d-none d-lg-block">
                @include('events._action', ['event' => $event, 'isHost' => $isHost, 'open' => $open, 'myRequest' => $myRequest, 'started' => $started, 'waitlistPosition' => $waitlistPosition ?? null, 'percent' => $percent, 'statusLabels' => $statusLabels, 'statusClasses' => $statusClasses])
            </div>

            @php
                $shareUrl = route('events.show', $event);
                $shareText = $event->title.' — '.$event->starts_at->translatedFormat('j F Y');
            @endphp
            <div class="liqa-card p-3 mb-3" data-share data-url="{{ $shareUrl }}" data-title="{{ $event->title }}" data-text="{{ $shareText }}">
                <div class="fw-bold text-white mb-2 d-flex align-items-center gap-2"><x-icon name="send" :size="18" /> {{ __('شارك هذا الحدث') }}</div>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-liqa-outline" data-share-native hidden>{{ __('مشاركة…') }}</button>
                    <div class="d-flex gap-2">
                        <a class="btn btn-liqa-outline flex-fill" target="_blank" rel="noopener" href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}">LinkedIn</a>
                        <a class="btn btn-liqa-outline flex-fill" target="_blank" rel="noopener" href="https://wa.me/?text={{ urlencode($shareText.' '.$shareUrl) }}">WhatsApp</a>
                        <a class="btn btn-liqa-outline flex-fill" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text={{ urlencode($shareText) }}&url={{ urlencode($shareUrl) }}">X</a>
                    </div>
                    <button type="button" class="btn btn-liqa-outline" data-share-copy data-done="{{ __('تم نسخ الرابط ✓') }}">{{ __('نسخ الرابط') }}</button>
                </div>
            </div>

            @if ($myAttendance)
                <div class="liqa-card p-3 mb-3 text-center">
                    <div class="fw-bold text-white mb-1">{{ __('شهادة حضورك جاهزة') }}</div>
                    <p class="small text-secondary mb-2">{{ __('احتفظ بها أو أضفها إلى ملفك على لينكدإن.') }}</p>
                    <a href="{{ route('events.certificate', $event) }}" class="btn btn-liqa w-100">{{ __('عرض الشهادة وطباعتها') }}</a>
                </div>
            @endif

            @if (auth()->check() && ! $event->isCancelled() && ! $ended && ($isHost || ($myRequest && $myRequest->status === 'accepted')))
                @php
                    $gcal = 'https://calendar.google.com/calendar/render?'.http_build_query([
                        'action' => 'TEMPLATE',
                        'text' => $event->title,
                        'dates' => $event->starts_at->copy()->utc()->format('Ymd\THis\Z').'/'.$event->endsAt()->utc()->format('Ymd\THis\Z'),
                        'details' => $event->description."\n\n".route('events.show', $event).(\App\Support\LiveKit::configured() ? "\n".__('الغرفة المباشرة: ').route('events.room', $event) : ''),
                        'location' => $event->placeLabel(),
                    ]);
                    $outlook = 'https://outlook.live.com/calendar/0/deeplink/compose?'.http_build_query([
                        'path' => '/calendar/action/compose',
                        'rru' => 'addevent',
                        'subject' => $event->title,
                        'startdt' => $event->starts_at->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
                        'enddt' => $event->endsAt()->utc()->format('Y-m-d\TH:i:s\Z'),
                        'body' => $event->description."\n\n".route('events.show', $event),
                        'location' => $event->placeLabel(),
                    ]);
                @endphp
                <div class="liqa-card p-3 mb-3">
                    <div class="fw-bold text-white mb-2 d-flex align-items-center gap-2"><x-icon name="calendar" :size="18" /> {{ __('أضف إلى تقويمك') }}</div>
                    <div class="d-grid gap-2">
                        <a href="{{ $gcal }}" target="_blank" rel="noopener" class="btn btn-liqa-outline">{{ __('تقويم Google') }}</a>
                        <a href="{{ $outlook }}" target="_blank" rel="noopener" class="btn btn-liqa-outline">Outlook</a>
                        <a href="{{ route('events.calendar', $event) }}" class="btn btn-liqa-outline">{{ __('تنزيل ملف (Apple / Outlook)') }}</a>
                    </div>
                </div>
            @endif

            @if ($series->isNotEmpty())
                <div class="liqa-card p-3 mb-3">
                    <div class="fw-bold text-white mb-2 d-flex align-items-center gap-2"><x-icon name="calendar" :size="18" /> {{ __('لقاءات متكررة') }}</div>
                    <p class="small text-secondary mb-2">{{ __('هذا اللقاء جزء من سلسلة. مواعيد بقية اللقاءات:') }}</p>
                    <div class="d-grid gap-1">
                        @foreach ($series as $s)
                            <a href="{{ route('events.show', $s) }}" class="small text-decoration-none d-flex justify-content-between gap-2">
                                <span>{{ $s->starts_at->translatedFormat('l j F') }}</span>
                                <span class="text-secondary">{{ $s->starts_at->translatedFormat('g:i A') }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (auth()->check() && ! $isHost)
                @php $saved = auth()->user()->hasSaved($event); @endphp
                <form method="POST" action="{{ route('events.save', $event) }}" class="mb-3 js-save-form">
                    @csrf
                    <button type="submit" class="btn btn-liqa-outline w-100 d-flex align-items-center justify-content-center gap-2 save-btn-wide {{ $saved ? 'is-saved' : '' }}">
                        <x-icon name="bookmark" :size="18" />
                        <span class="js-save-label">{{ $saved ? __('محفوظ — اضغط للإزالة') : __('حفظ الحدث') }}</span>
                    </button>
                </form>
            @endif

            @if (auth()->check() && ! $isHost)
                <div class="text-center mb-3">
                    <a href="{{ route('reports.create', ['type' => 'event', 'id' => $event->id]) }}" class="small text-secondary text-decoration-none">{{ __('⚑ إبلاغ عن هذا الحدث') }}</a>
                </div>
            @endif

            <div class="liqa-card p-3 p-md-4">
                <h2 class="h6 liqa-title mb-3 d-flex align-items-center gap-2"><x-icon name="users" :size="18" /> {{ __('المشاركون المؤكَّدون') }}</h2>
                @if ($accepted->isEmpty())
                    <p class="small text-secondary mb-0">{{ __('لم يتم قبول أي مشارك بعد.') }}</p>
                @else
                    <div class="d-grid gap-2">
                        @foreach ($accepted as $req)
                            <a href="{{ route('users.show', $req->user) }}" class="d-flex align-items-center gap-2 text-decoration-none">
                                <x-avatar :user="$req->user" :size="34" />
                                <span class="small text-white">{{ $req->user->name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var box = document.querySelector('[data-share]');
    if (!box) { return; }
    var url = box.dataset.url;
    var native = box.querySelector('[data-share-native]');
    var copy = box.querySelector('[data-share-copy]');
    if (navigator.share) {
        native.hidden = false;
        native.addEventListener('click', function () {
            navigator.share({ title: box.dataset.title, text: box.dataset.text, url: url }).catch(function () {});
        });
    }
    copy.addEventListener('click', function () {
        var label = copy.textContent;
        function done() { copy.textContent = copy.dataset.done; setTimeout(function () { copy.textContent = label; }, 2000); }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(done, function () { window.prompt('', url); });
        } else { window.prompt('', url); }
    });
})();
</script>
@endsection
