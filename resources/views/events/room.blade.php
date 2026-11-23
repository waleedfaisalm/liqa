@extends('layouts.app')
@section('title', __('الغرفة المباشرة — ').$event->title)
@section('content')
    <div data-bs-theme="dark" class="room{{ $isHost ? ' is-owner is-manager' : ($isCohost ? ' is-manager' : '') }}" id="room"
         data-url="{{ $serverUrl }}"
         data-token="{{ $token }}"
         data-host="{{ $isHost ? '1' : '0' }}"
         data-cohost="{{ $isCohost ? '1' : '0' }}"
         data-lobby="{{ $lobbyOn ? '1' : '0' }}"
         data-knock-url="{{ route('events.room.knock', $event) }}"
         data-status-url="{{ route('events.room.status', $event) }}"
         data-token-url="{{ route('events.room.token', $event) }}"
         data-waiting-url="{{ route('events.room.waiting', $event) }}"
         data-decide-url="{{ route('events.room.decide', $event) }}"
         data-mute-join="{{ $muteJoin ? '1' : '0' }}"
         data-mutejoin-url="{{ route('events.room.mutejoin', $event) }}"
         data-end-url="{{ route('events.room.end', $event) }}"
         data-lobby-url="{{ route('events.room.lobby', $event) }}"
         data-link-url="{{ route('events.room.link', $event) }}"
         data-cohost-url="{{ route('events.room.cohost', $event) }}"
         data-mute-url="{{ route('events.room.mute', $event) }}"
         data-remove-url="{{ route('events.room.remove', $event) }}"
         data-room-link="{{ route('events.room', $event) }}"
         data-ping-url="{{ route('events.room.ping', $event) }}"
         data-csrf="{{ csrf_token() }}"
         data-exit="{{ route('events.show', $event) }}">

        <div class="room-head d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <a href="{{ route('events.show', $event) }}" class="small text-decoration-none">{{ __('← العودة إلى صفحة الحدث') }}</a>
                <h1 class="h4 liqa-title mb-0 mt-1">{{ $event->title }}</h1>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="liqa-badge liqa-badge--pink d-none" id="roomTimer" title="{{ __('مدة وجودك في الغرفة') }}">00:00</span>
                <span class="liqa-badge liqa-badge--green d-none" id="roomCount"></span>
            </div>
        </div>

        {{-- شاشة ما قبل الدخول --}}
        <div class="liqa-card p-4 text-center room-prejoin" id="roomPrejoin">
            <h2 class="h5 liqa-title mb-2">{{ __('جاهز للانضمام؟') }}</h2>
            <p class="small text-secondary mb-4">{{ __('اختر إن كنت تريد تشغيل الميكروفون والكاميرا عند الدخول. يمكنك تغييرهما في أي وقت من الغرفة.') }}</p>
            <div class="d-flex flex-wrap justify-content-center gap-4 mb-4">
                <label class="form-check d-flex align-items-center gap-2 m-0">
                    <input type="checkbox" class="form-check-input m-0" id="preMic" checked> <span>{{ __('تشغيل الميكروفون') }}</span>
                </label>
                <label class="form-check d-flex align-items-center gap-2 m-0">
                    <input type="checkbox" class="form-check-input m-0" id="preCam"> <span>{{ __('تشغيل الكاميرا') }}</span>
                </label>
            </div>
            <div class="pre-devices mb-4">
                <button type="button" class="btn btn-sm btn-liqa-outline" id="preTest">{{ __('اختبار الميكروفون والكاميرا واختيار الجهاز') }}</button>
                <div class="pre-devices__box d-none" id="preBox">
                    <video id="preView" class="pre-view" autoplay playsinline muted></video>
                    <div class="pre-level" title="{{ __('مستوى صوت الميكروفون') }}"><span id="preLevel"></span></div>
                    <div class="row g-2 text-start mt-1">
                        <div class="col-md-6"><label class="liqa-label d-block small" for="selMic">{{ __('الميكروفون') }}</label><select id="selMic" class="form-select form-select-sm"></select></div>
                        <div class="col-md-6"><label class="liqa-label d-block small" for="selCam">{{ __('الكاميرا') }}</label><select id="selCam" class="form-select form-select-sm"></select></div>
                    </div>
                </div>
            </div>
            <p class="small text-warning mb-3 d-none" id="muteJoinNote">{{ __('طلب المنظّم دخول المشاركين بميكروفونات مكتومة، ويمكنك تشغيل الميكروفون بعد الدخول.') }}</p>
            @unless ($isHost)
                @if ($viaLink)
                    <p class="small text-secondary mb-3">{{ __('دخلت عبر رابط الغرفة. سيصل طلبك إلى المنظّم، وتدخل بعد موافقته.') }}</p>
                @elseif ($lobbyOn)
                    <p class="small text-secondary mb-3">{{ __('سيصل طلبك إلى المنظّم، وتدخل الغرفة بعد موافقته.') }}</p>
                @endif
            @endunless
            <button type="button" class="btn btn-liqa px-5" id="roomJoin">{{ $isHost ? __('ابدأ الغرفة') : (($lobbyOn || $viaLink) ? __('اطلب الدخول') : __('انضم إلى الغرفة')) }}</button>
            <div class="small text-danger mt-3 d-none" id="roomError"></div>
        </div>

        {{-- شاشة الانتظار (للمشارك) --}}
        <div class="liqa-card p-4 text-center room-prejoin d-none" id="roomWait">
            <div class="room-spinner mx-auto mb-3"></div>
            <h2 class="h5 liqa-title mb-2" id="roomWaitTitle">{{ __('بانتظار موافقة المنظّم…') }}</h2>
            <p class="small text-secondary mb-3" id="roomWaitText">{{ __('أُرسل طلبك، وستدخل الغرفة تلقائيًا بمجرد قبوله. أبقِ هذه الصفحة مفتوحة.') }}</p>
            <a href="{{ route('events.show', $event) }}" class="btn btn-liqa-outline btn-sm" id="roomWaitCancel">{{ __('إلغاء والعودة') }}</a>
        </div>

        {{-- الغرفة --}}
        <div class="room-stage d-none" id="roomStage">
            {{-- لوحة التحكم: تظهر للمنظّم والمنظّم المساعد --}}
            <div class="room-lobbybar mb-3" id="roomLobbyBar">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 owner-only">
                    <label class="form-check d-flex align-items-center gap-2 m-0 small">
                        <input type="checkbox" class="form-check-input m-0" id="lobbyToggle" {{ $lobbyOn ? 'checked' : '' }}>
                        <span>{{ __('غرفة الانتظار (لا يدخل أحد إلا بموافقتك)') }}</span>
                    </label>
                    <button type="button" class="btn btn-sm btn-liqa-outline" id="copyLink">{{ __('نسخ رابط الغرفة') }}</button>
                </div>
                <label class="form-check d-flex align-items-center gap-2 m-0 small mt-2 owner-only">
                    <input type="checkbox" class="form-check-input m-0" id="linkToggle" {{ $linkOn ? 'checked' : '' }}>
                    <span>{{ __('السماح بالدخول بالرابط لمن ليس مشاركًا في الحدث (بعد موافقتك)') }}</span>
                </label>
                <label class="form-check d-flex align-items-center gap-2 m-0 small mt-2 owner-only">
                    <input type="checkbox" class="form-check-input m-0" id="muteJoinToggle" {{ $muteJoin ? 'checked' : '' }}>
                    <span>{{ __('دخول المشاركين بميكروفونات مكتومة') }}</span>
                </label>
                <label class="form-check d-flex align-items-center gap-2 m-0 small mt-2 owner-only">
                    <input type="checkbox" class="form-check-input m-0" id="chatToggle" checked>
                    <span>{{ __('السماح للمشاركين بالكتابة في المحادثة') }}</span>
                </label>
                <div class="room-waiting d-none" id="roomWaiting">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <strong class="small">{{ __('بانتظار الدخول') }} <span class="liqa-badge liqa-badge--pink" id="waitCount">0</span></strong>
                        <button type="button" class="btn btn-sm btn-liqa" id="admitAll">{{ __('قبول الجميع') }}</button>
                    </div>
                    <div id="waitList"></div>
                </div>
            </div>

            <div class="room-grid" id="roomGrid"></div>
            <div id="roomAudios" hidden></div>
            <div class="room-toasts" id="roomToasts" aria-live="polite"></div>
            <div class="room-reacts" id="roomReacts" aria-hidden="true"></div>

            {{-- المشاركون --}}
            <aside class="room-chat d-none" id="roomPeople" aria-label="{{ __('المشاركون') }}">
                <div class="room-chat__head">
                    <strong>{{ __('المشاركون') }} <span class="liqa-badge liqa-badge--green" id="peopleTotal">0</span></strong>
                    <button type="button" class="btn btn-sm btn-liqa-outline" id="peopleClose">{{ __('إغلاق') }}</button>
                </div>
                <div class="people-tools manager-only">
                    <button type="button" class="btn btn-sm btn-liqa-outline" id="muteAll">{{ __('كتم الجميع') }}</button>
                    <button type="button" class="btn btn-sm btn-liqa-outline" id="lowerAll">{{ __('إنزال كل الأيدي') }}</button>
                </div>
                <div class="room-chat__list people-list" id="peopleList"></div>
            </aside>

            {{-- المحادثة النصية --}}
            <aside class="room-chat d-none" id="roomChat" aria-label="{{ __('المحادثة') }}">
                <div class="room-chat__head">
                    <strong>{{ __('المحادثة') }}</strong>
                    <button type="button" class="btn btn-sm btn-liqa-outline" id="chatClose">{{ __('إغلاق') }}</button>
                </div>
                <div class="room-chat__list" id="chatList">
                    <div class="room-chat__empty small text-secondary" id="chatEmpty">{{ __('لا توجد رسائل بعد. المحادثة تخص الحاضرين الآن ولا تُحفظ بعد انتهاء الغرفة.') }}</div>
                </div>
                <form class="room-chat__form" id="chatForm" autocomplete="off">
                    <input type="text" class="form-control" id="chatInput" maxlength="500" placeholder="{{ __('اكتب رسالة…') }}" aria-label="{{ __('رسالة') }}">
                    <button type="submit" class="btn btn-liqa" id="chatSend">{{ __('إرسال') }}</button>
                </form>
            </aside>

            <div class="room-pop d-none" id="devMenu"></div>
            <div class="room-pop d-none" id="leaveMenu">
                <button type="button" class="room-pop__item" id="leaveOnly">{{ __('مغادرة فقط (يستمر الاجتماع)') }}</button>
                <button type="button" class="room-pop__item is-danger" id="endAll">{{ __('إنهاء الاجتماع للجميع') }}</button>
            </div>

            <div class="room-reactbar d-none" id="reactBar">
                <button type="button" data-emoji="👍" aria-label="{{ __('إعجاب') }}">👍</button>
                <button type="button" data-emoji="👏" aria-label="{{ __('تصفيق') }}">👏</button>
                <button type="button" data-emoji="❤️" aria-label="{{ __('قلب') }}">❤️</button>
                <button type="button" data-emoji="😂" aria-label="{{ __('ضحك') }}">😂</button>
                <button type="button" data-emoji="🎉" aria-label="{{ __('احتفال') }}">🎉</button>
            </div>

            <div class="room-controls" id="roomControls">
                <div class="room-btn-group">
                    <button type="button" class="room-btn" id="btnMic" data-on="1"><span class="room-btn__icon" id="icoMic"></span><span class="room-btn__label">{{ __('الميكروفون') }}</span></button>
                    <button type="button" class="room-caret" id="caretMic" aria-label="{{ __('اختيار الميكروفون') }}">▾</button>
                </div>
                <div class="room-btn-group">
                    <button type="button" class="room-btn" id="btnCam" data-on="1"><span class="room-btn__icon" id="icoCam"></span><span class="room-btn__label">{{ __('الكاميرا') }}</span></button>
                    <button type="button" class="room-caret" id="caretCam" aria-label="{{ __('اختيار الكاميرا') }}">▾</button>
                </div>
                <button type="button" class="room-btn" id="btnShare" hidden><span class="room-btn__icon" id="icoShare"></span><span class="room-btn__label">{{ __('مشاركة الشاشة') }}</span></button>
                <button type="button" class="room-btn" id="btnHand" title="{{ __('رفع اليد') }}"><span class="room-btn__icon" id="icoHand"></span><span class="room-btn__label">{{ __('رفع اليد') }}</span></button>
                <button type="button" class="room-btn" id="btnReact" title="{{ __('ردود الفعل') }}"><span class="room-btn__icon" id="icoReact"></span><span class="room-btn__label">{{ __('تفاعل') }}</span></button>
                <button type="button" class="room-btn" id="btnPeople"><span class="room-btn__icon" id="icoPeople"></span><span class="room-btn__label">{{ __('المشاركون') }}</span><span class="room-btn__dot d-none" id="handDot">0</span></button>
                <button type="button" class="room-btn manager-only" id="btnManage" title="{{ __('إدارة الغرفة') }}"><span class="room-btn__icon" id="icoManage"></span><span class="room-btn__label">{{ __('الإدارة') }}</span></button>
                <button type="button" class="room-btn" id="btnChat"><span class="room-btn__icon" id="icoChat"></span><span class="room-btn__label">{{ __('المحادثة') }}</span><span class="room-btn__dot d-none" id="chatDot">0</span></button>
                <button type="button" class="room-btn room-btn--leave" id="btnLeave"><span class="room-btn__icon" id="icoLeave"></span><span class="room-btn__label">{{ __('مغادرة') }}</span></button>
            </div>
        </div>
    </div>

    @if (app()->getLocale() !== 'ar')
        <script>window.LIQA_T = @json(collect(json_decode(file_get_contents(resource_path('js/room-keys.json')), true))->mapWithKeys(fn ($k) => [$k => __($k)]));</script>
    @endif
    @vite('resources/js/room.js')
@endsection
