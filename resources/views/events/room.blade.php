@extends('layouts.app')

@section('title', 'الغرفة المباشرة — '.$event->title)

@section('content')
    <div class="room" id="room"
         data-url="{{ $serverUrl }}"
         data-token="{{ $token }}"
         data-host="{{ $isHost ? '1' : '0' }}"
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
            <button type="button" class="btn btn-liqa px-5" id="roomJoin">انضم إلى الغرفة</button>
            <div class="small text-danger mt-3 d-none" id="roomError"></div>
        </div>

        {{-- الغرفة --}}
        <div class="room-stage d-none" id="roomStage">
            <div class="room-grid" id="roomGrid"></div>
            <div id="roomAudios" hidden></div>

            <div class="room-controls" id="roomControls">
                <button type="button" class="room-btn" id="btnMic" data-on="1"><span class="room-btn__icon" id="icoMic"></span><span class="room-btn__label">الميكروفون</span></button>
                <button type="button" class="room-btn" id="btnCam" data-on="1"><span class="room-btn__icon" id="icoCam"></span><span class="room-btn__label">الكاميرا</span></button>
                <button type="button" class="room-btn" id="btnShare" hidden><span class="room-btn__icon" id="icoShare"></span><span class="room-btn__label">مشاركة الشاشة</span></button>
                <button type="button" class="room-btn room-btn--leave" id="btnLeave"><span class="room-btn__icon" id="icoLeave"></span><span class="room-btn__label">مغادرة</span></button>
            </div>
        </div>
    </div>

    @vite('resources/js/room.js')
@endsection
