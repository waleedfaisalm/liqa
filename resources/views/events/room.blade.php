@extends('layouts.app')

@section('title', 'الغرفة المباشرة — '.$event->title)

@section('content')
    <div class="room" id="room"
         data-url="{{ $serverUrl }}"
         data-token="{{ $token }}"
         data-host="{{ $isHost ? '1' : '0' }}"
         data-lobby="{{ $lobbyOn ? '1' : '0' }}"
         data-knock-url="{{ route('events.room.knock', $event) }}"
         data-status-url="{{ route('events.room.status', $event) }}"
         data-token-url="{{ route('events.room.token', $event) }}"
         data-waiting-url="{{ route('events.room.waiting', $event) }}"
         data-decide-url="{{ route('events.room.decide', $event) }}"
         data-lobby-url="{{ route('events.room.lobby', $event) }}"
         data-remove-url="{{ route('events.room.remove', $event) }}"
         data-csrf="{{ csrf_token() }}"
         data-exit="{{ route('events.show', $event) }}">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <a href="{{ route('events.show', $event) }}" class="small text-decoration-none">← العودة إلى صفحة الحدث</a>
                <h1 class="h4 liqa-title mb-0 mt-1">{{ $event->title }}</h1>
            </div>
            <span class="liqa-badge liqa-badge--green d-none" id="roomCount"></span>
        </div>

        {{-- شاشة ما قبل الدخول --}}
        <div class="liqa-card p-4 text-center room-prejoin" id="roomPrejoin">
            <h2 class="h5 liqa-title mb-2">جاهز للانضمام؟</h2>
            <p class="small text-secondary mb-4">اختر إن كنت تريد تشغيل الميكروفون والكاميرا عند الدخول. يمكنك تغييرهما في أي وقت من الغرفة.</p>
            <div class="d-flex flex-wrap justify-content-center gap-4 mb-4">
                <label class="form-check d-flex align-items-center gap-2 m-0">
                    <input type="checkbox" class="form-check-input m-0" id="preMic" checked> <span>تشغيل الميكروفون</span>
                </label>
                <label class="form-check d-flex align-items-center gap-2 m-0">
                    <input type="checkbox" class="form-check-input m-0" id="preCam"> <span>تشغيل الكاميرا</span>
                </label>
            </div>
            @unless ($isHost)
                @if ($lobbyOn)
                    <p class="small text-secondary mb-3">سيصل طلبك إلى المنظّم، وتدخل الغرفة بعد موافقته.</p>
                @endif
            @endunless
            <button type="button" class="btn btn-liqa px-5" id="roomJoin">{{ $isHost ? 'ابدأ الغرفة' : ($lobbyOn ? 'اطلب الدخول' : 'انضم إلى الغرفة') }}</button>
            <div class="small text-danger mt-3 d-none" id="roomError"></div>
        </div>

        {{-- شاشة الانتظار (للمشارك) --}}
        <div class="liqa-card p-4 text-center room-prejoin d-none" id="roomWait">
            <div class="room-spinner mx-auto mb-3"></div>
            <h2 class="h5 liqa-title mb-2" id="roomWaitTitle">بانتظار موافقة المنظّم…</h2>
            <p class="small text-secondary mb-3" id="roomWaitText">أُرسل طلبك، وستدخل الغرفة تلقائيًا بمجرد قبوله. أبقِ هذه الصفحة مفتوحة.</p>
            <a href="{{ route('events.show', $event) }}" class="btn btn-liqa-outline btn-sm" id="roomWaitCancel">إلغاء والعودة</a>
        </div>

        {{-- الغرفة --}}
        <div class="room-stage d-none" id="roomStage">
            @if ($isHost)
                <div class="room-lobbybar mb-3" id="roomLobbyBar">
                    <label class="form-check d-flex align-items-center gap-2 m-0 small">
                        <input type="checkbox" class="form-check-input m-0" id="lobbyToggle" {{ $lobbyOn ? 'checked' : '' }}>
                        <span>غرفة الانتظار (لا يدخل أحد إلا بموافقتك)</span>
                    </label>
                    <label class="form-check d-flex align-items-center gap-2 m-0 small mt-2">
                        <input type="checkbox" class="form-check-input m-0" id="chatToggle" checked>
                        <span>السماح للمشاركين بالكتابة في المحادثة</span>
                    </label>
                    <div class="room-waiting d-none" id="roomWaiting">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <strong class="small">بانتظار الدخول <span class="liqa-badge liqa-badge--pink" id="waitCount">0</span></strong>
                            <button type="button" class="btn btn-sm btn-liqa" id="admitAll">قبول الجميع</button>
                        </div>
                        <div id="waitList"></div>
                    </div>
                </div>
            @endif
            <div class="room-grid" id="roomGrid"></div>
            <div id="roomAudios" hidden></div>

            {{-- المحادثة النصية --}}
            <aside class="room-chat d-none" id="roomChat" aria-label="المحادثة">
                <div class="room-chat__head">
                    <strong>المحادثة</strong>
                    <button type="button" class="btn btn-sm btn-liqa-outline" id="chatClose">إغلاق</button>
                </div>
                <div class="room-chat__list" id="chatList">
                    <div class="room-chat__empty small text-secondary" id="chatEmpty">لا توجد رسائل بعد. المحادثة تخص الحاضرين الآن ولا تُحفظ بعد انتهاء الغرفة.</div>
                </div>
                <form class="room-chat__form" id="chatForm" autocomplete="off">
                    <input type="text" class="form-control" id="chatInput" maxlength="500" placeholder="اكتب رسالة…" aria-label="رسالة">
                    <button type="submit" class="btn btn-liqa" id="chatSend">إرسال</button>
                </form>
            </aside>

            <div class="room-controls" id="roomControls">
                <button type="button" class="room-btn" id="btnMic" data-on="1"><span class="room-btn__icon" id="icoMic"></span><span class="room-btn__label">الميكروفون</span></button>
                <button type="button" class="room-btn" id="btnCam" data-on="1"><span class="room-btn__icon" id="icoCam"></span><span class="room-btn__label">الكاميرا</span></button>
                <button type="button" class="room-btn" id="btnChat"><span class="room-btn__icon" id="icoChat"></span><span class="room-btn__label">المحادثة</span><span class="room-btn__dot d-none" id="chatDot">0</span></button>
                <button type="button" class="room-btn" id="btnShare" hidden><span class="room-btn__icon" id="icoShare"></span><span class="room-btn__label">مشاركة الشاشة</span></button>
                <button type="button" class="room-btn room-btn--leave" id="btnLeave"><span class="room-btn__icon" id="icoLeave"></span><span class="room-btn__label">مغادرة</span></button>
            </div>
        </div>
    </div>

    @vite('resources/js/room.js')
@endsection
