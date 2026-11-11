import { Room, RoomEvent, Track, DisconnectReason } from 'livekit-client';

const root = document.getElementById('room');
if (root) {
    const grid = document.getElementById('roomGrid');
    const audios = document.getElementById('roomAudios');
    const prejoin = document.getElementById('roomPrejoin');
    const stage = document.getElementById('roomStage');
    const errBox = document.getElementById('roomError');
    const countEl = document.getElementById('roomCount');
    const isHost = root.dataset.host === '1';

    const ICONS = {
        mic: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="3" width="6" height="12" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/></svg>',
        micOff: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M9 9v2a3 3 0 0 0 5 2.2M15 10V6a3 3 0 0 0-5.8-1M5 11a7 7 0 0 0 11 5.7M19 11a7 7 0 0 1-.6 2.8M12 18v3"/></svg>',
        cam: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="13" height="12" rx="2"/><path d="M16 10l5-3v10l-5-3"/></svg>',
        camOff: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M7 6h7a2 2 0 0 1 2 2v3m0 3v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 .6-1.4M16 10l5-3v10l-3-1.8"/></svg>',
        share: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4M9 10l3-3 3 3M12 7v6"/></svg>',
        leave: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h5M15 8l4 4-4 4M19 12H9"/></svg>',
        kick: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>',
    };
    document.getElementById('icoMic').innerHTML = ICONS.mic;
    document.getElementById('icoCam').innerHTML = ICONS.cam;
    document.getElementById('icoShare').innerHTML = ICONS.share;
    document.getElementById('icoLeave').innerHTML = ICONS.leave;

    const api = async (url, method = 'GET', body = null) => {
        const res = await fetch(url, {
            method,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf },
            body: body ? JSON.stringify(body) : null,
        });
        let data = {};
        try { data = await res.json(); } catch (e) { /* فارغ */ }
        return { ok: res.ok, status: res.status, data };
    };

    let room = null;
    const tiles = new Map(); // identity -> {el, video, ...}
    let leaving = false;

    const meta = (p) => {
        try { return JSON.parse(p.metadata || '{}'); } catch { return {}; }
    };

    function showError(msg) {
        errBox.textContent = msg;
        errBox.classList.remove('d-none');
    }

    function ensureTile(p) {
        let t = tiles.get(p.identity);
        if (t) return t;

        const m = meta(p);
        const el = document.createElement('div');
        el.className = 'room-tile';
        el.dataset.identity = p.identity;

        const avatar = document.createElement('div');
        avatar.className = 'room-tile__avatar';
        if (m.avatar) {
            const img = document.createElement('img');
            img.src = m.avatar;
            img.alt = '';
            avatar.appendChild(img);
        } else {
            const ini = document.createElement('span');
            ini.className = 'room-tile__initial';
            ini.textContent = m.initial || (p.name || '?').charAt(0);
            avatar.appendChild(ini);
        }

        const video = document.createElement('video');
        video.className = 'room-tile__video';
        video.autoplay = true;
        video.playsInline = true;
        video.muted = true; // الصوت يُشغَّل من عناصر منفصلة

        const label = document.createElement('div');
        label.className = 'room-tile__label';
        const nameEl = document.createElement('span');
        nameEl.className = 'room-tile__name';
        nameEl.textContent = (p.name || 'عضو') + (p.isLocal ? ' (أنت)' : '');
        const micEl = document.createElement('span');
        micEl.className = 'room-tile__mic';
        label.append(micEl, nameEl);
        if (m.host) {
            const badge = document.createElement('span');
            badge.className = 'room-tile__host';
            badge.textContent = 'المنظّم';
            label.appendChild(badge);
        }

        el.append(avatar, video, label);

        if (isHost && !p.isLocal) {
            const kick = document.createElement('button');
            kick.type = 'button';
            kick.className = 'room-tile__kick';
            kick.title = 'إخراج من الغرفة';
            kick.innerHTML = ICONS.kick;
            kick.addEventListener('click', () => removeParticipant(p, kick));
            el.appendChild(kick);
        }

        grid.appendChild(el);
        t = { el, video, micEl, avatar };
        tiles.set(p.identity, t);
        updateLayout();
        return t;
    }

    function refresh(p) {
        const t = ensureTile(p);

        const cam = p.getTrackPublication(Track.Source.Camera);
        const screen = p.getTrackPublication(Track.Source.ScreenShare);
        const pub = screen && screen.track && !screen.isMuted ? screen : cam;
        const videoTrack = pub && pub.track && !pub.isMuted ? pub.track : null;

        if (videoTrack) {
            if (t.video.dataset.sid !== videoTrack.sid) {
                videoTrack.attach(t.video);
                t.video.dataset.sid = videoTrack.sid;
            }
            t.el.classList.add('has-video');
        } else {
            if (t.video.dataset.sid) {
                const prev = (cam && cam.track) || (screen && screen.track);
                if (prev) prev.detach(t.video);
                delete t.video.dataset.sid;
            }
            t.el.classList.remove('has-video');
        }

        t.el.classList.toggle('is-screen', !!(pub === screen && videoTrack));

        const mic = p.getTrackPublication(Track.Source.Microphone);
        const micOn = !!(mic && mic.track && !mic.isMuted);
        t.micEl.innerHTML = micOn ? '' : ICONS.micOff;
        t.micEl.classList.toggle('is-off', !micOn);
    }

    function remove(p) {
        const t = tiles.get(p.identity);
        if (t) {
            t.el.remove();
            tiles.delete(p.identity);
            updateLayout();
        }
    }

    function updateLayout() {
        const n = tiles.size;
        grid.dataset.count = String(Math.min(n, 9));
        countEl.textContent = n + ' في الغرفة';
        countEl.classList.toggle('d-none', n === 0);
    }

    async function removeParticipant(p, btn) {
        if (!confirm('إخراج ' + (p.name || 'هذا العضو') + ' من الغرفة؟ لن يستطيع الدخول مجددًا.')) return;
        btn.disabled = true;
        try {
            await fetch(root.dataset.removeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf },
                body: JSON.stringify({ identity: p.identity }),
            });
        } catch (e) { /* يُتجاهل */ }
        btn.disabled = false;
    }

    function setButton(btn, icoEl, on, iconOn, iconOff) {
        btn.dataset.on = on ? '1' : '0';
        btn.classList.toggle('is-off', !on);
        icoEl.innerHTML = on ? iconOn : iconOff;
    }

    function wireControls() {
        const btnMic = document.getElementById('btnMic');
        const btnCam = document.getElementById('btnCam');
        const btnShare = document.getElementById('btnShare');
        const btnLeave = document.getElementById('btnLeave');
        const lp = room.localParticipant;

        const sync = () => {
            setButton(btnMic, document.getElementById('icoMic'), lp.isMicrophoneEnabled, ICONS.mic, ICONS.micOff);
            setButton(btnCam, document.getElementById('icoCam'), lp.isCameraEnabled, ICONS.cam, ICONS.camOff);
            btnShare.classList.toggle('is-active', lp.isScreenShareEnabled);
            refresh(lp);
        };

        btnMic.addEventListener('click', async () => {
            try { await lp.setMicrophoneEnabled(!lp.isMicrophoneEnabled); } catch (e) { alert('تعذّر الوصول إلى الميكروفون. تأكد من منح الإذن للمتصفح.'); }
            sync();
        });
        btnCam.addEventListener('click', async () => {
            try { await lp.setCameraEnabled(!lp.isCameraEnabled); } catch (e) { alert('تعذّر الوصول إلى الكاميرا. تأكد من منح الإذن للمتصفح.'); }
            sync();
        });

        if (navigator.mediaDevices && navigator.mediaDevices.getDisplayMedia) {
            btnShare.hidden = false;
            btnShare.addEventListener('click', async () => {
                try { await lp.setScreenShareEnabled(!lp.isScreenShareEnabled); } catch (e) { /* أُلغيت المشاركة */ }
                sync();
            });
        }

        btnLeave.addEventListener('click', () => {
            leaving = true;
            room.disconnect();
            window.location.href = root.dataset.exit;
        });

        room.on(RoomEvent.LocalTrackPublished, sync);
        room.on(RoomEvent.LocalTrackUnpublished, sync);
        sync();

        return sync;
    }

    async function join(token, serverUrl) {
        const joinBtn = document.getElementById('roomJoin');
        joinBtn.disabled = true;
        errBox.classList.add('d-none');

        const wantMic = document.getElementById('preMic').checked;
        const wantCam = document.getElementById('preCam').checked;

        room = new Room({ adaptiveStream: true, dynacast: true });

        room
            .on(RoomEvent.ParticipantConnected, (p) => refresh(p))
            .on(RoomEvent.ParticipantDisconnected, (p) => remove(p))
            .on(RoomEvent.TrackSubscribed, (track, pub, p) => {
                if (track.kind === Track.Kind.Audio) {
                    audios.appendChild(track.attach());
                }
                refresh(p);
            })
            .on(RoomEvent.TrackUnsubscribed, (track, pub, p) => {
                track.detach().forEach((el) => el.remove());
                refresh(p);
            })
            .on(RoomEvent.TrackMuted, (pub, p) => refresh(p))
            .on(RoomEvent.TrackUnmuted, (pub, p) => refresh(p))
            .on(RoomEvent.ActiveSpeakersChanged, (speakers) => {
                const ids = new Set(speakers.map((s) => s.identity));
                tiles.forEach((t, id) => t.el.classList.toggle('is-speaking', ids.has(id)));
            })
            .on(RoomEvent.Disconnected, (reason) => {
                if (leaving) return;
                const removed = reason === DisconnectReason.PARTICIPANT_REMOVED;
                alert(removed ? 'أخرجك المنظّم من الغرفة.' : 'انقطع الاتصال بالغرفة.');
                window.location.href = root.dataset.exit;
            });

        try {
            await room.connect(serverUrl, token);
            await room.startAudio();
        } catch (e) {
            joinBtn.disabled = false;
            document.getElementById('roomWait').classList.add('d-none');
            prejoin.classList.remove('d-none');
            showError('تعذّر الاتصال بالغرفة. تحقق من اتصالك وأعد المحاولة.');
            return;
        }

        prejoin.classList.add('d-none');
        document.getElementById('roomWait').classList.add('d-none');
        stage.classList.remove('d-none');
        if (isHost) startHostPanel();

        refresh(room.localParticipant);
        room.remoteParticipants.forEach((p) => {
            refresh(p);
            p.trackPublications.forEach((pub) => {
                if (pub.track && pub.kind === Track.Kind.Audio) audios.appendChild(pub.track.attach());
            });
        });
        const sync = wireControls();

        try { if (wantMic) await room.localParticipant.setMicrophoneEnabled(true); } catch (e) { alert('تعذّر الوصول إلى الميكروفون.'); }
        try { if (wantCam) await room.localParticipant.setCameraEnabled(true); } catch (e) { alert('تعذّر الوصول إلى الكاميرا.'); }
        sync();
    }

    // ---- المشارك: طلب الدخول وانتظار الموافقة ----
    let pollTimer = null;
    const waitBox = document.getElementById('roomWait');

    function stopPoll() { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }

    function showWait(title, text, final = false) {
        prejoin.classList.add('d-none');
        waitBox.classList.remove('d-none');
        document.getElementById('roomWaitTitle').textContent = title;
        document.getElementById('roomWaitText').textContent = text;
        if (final) waitBox.querySelector('.room-spinner').classList.add('d-none');
    }

    async function enterWithToken() {
        const r = await api(root.dataset.tokenUrl, 'POST');
        if (!r.ok) {
            showWait('تعذّر الدخول', r.data.message || 'لم يُقبل دخولك.', true);
            return;
        }
        showWait('جارٍ الدخول إلى الغرفة…', 'لحظات ويتم الاتصال.');
        await join(r.data.token, r.data.url);
    }

    function handleStatus(status) {
        if (status === 'admitted') { stopPoll(); enterWithToken(); return true; }
        if (status === 'denied') { stopPoll(); showWait('رفض المنظّم دخولك', 'لم يُسمح لك بدخول هذه الغرفة.', true); return true; }
        if (status === 'removed') { stopPoll(); showWait('أخرجك المنظّم', 'لا يمكنك دخول هذه الغرفة مجددًا.', true); return true; }
        return false;
    }

    async function requestEntry() {
        const joinBtn = document.getElementById('roomJoin');
        joinBtn.disabled = true;
        errBox.classList.add('d-none');

        const r = await api(root.dataset.knockUrl, 'POST');
        if (!r.ok) {
            joinBtn.disabled = false;
            showError(r.data.message || 'تعذّر إرسال الطلب. أعد المحاولة.');
            return;
        }
        if (handleStatus(r.data.status)) return;

        showWait('بانتظار موافقة المنظّم…', 'أُرسل طلبك، وستدخل الغرفة تلقائيًا بمجرد قبوله. أبقِ هذه الصفحة مفتوحة.');
        pollTimer = setInterval(async () => {
            const s = await api(root.dataset.statusUrl);
            if (s.ok) handleStatus(s.data.status);
        }, 3000);
    }

    // ---- المنظّم: لوحة المنتظرين وغرفة الانتظار ----
    function startHostPanel() {
        const panel = document.getElementById('roomWaiting');
        const list = document.getElementById('waitList');
        const count = document.getElementById('waitCount');
        const toggle = document.getElementById('lobbyToggle');
        if (!panel) return;

        let lastCount = 0;

        const render = (items) => {
            list.innerHTML = '';
            items.forEach((u) => {
                const row = document.createElement('div');
                row.className = 'wait-row';
                const av = document.createElement('span');
                av.className = 'wait-row__avatar';
                if (u.avatar) { const im = document.createElement('img'); im.src = u.avatar; im.alt = ''; av.appendChild(im); } else { av.textContent = u.initial; }
                const nm = document.createElement('span');
                nm.className = 'wait-row__name';
                nm.textContent = u.name;
                const ok = document.createElement('button');
                ok.type = 'button'; ok.className = 'btn btn-sm btn-liqa'; ok.textContent = 'قبول';
                ok.addEventListener('click', async () => { ok.disabled = true; await api(root.dataset.decideUrl, 'POST', { action: 'admit', user_id: u.id }); poll(); });
                const no = document.createElement('button');
                no.type = 'button'; no.className = 'btn btn-sm btn-liqa-outline'; no.textContent = 'رفض';
                no.addEventListener('click', async () => { no.disabled = true; await api(root.dataset.decideUrl, 'POST', { action: 'deny', user_id: u.id }); poll(); });
                row.append(av, nm, ok, no);
                list.appendChild(row);
            });
            count.textContent = String(items.length);
            panel.classList.toggle('d-none', items.length === 0);
            if (items.length > lastCount) {
                try { document.title = '🔔 ' + items.length + ' بانتظار الدخول — ' + document.title.replace(/^🔔 \d+ بانتظار الدخول — /, ''); } catch (e) { /* يُتجاهل */ }
            }
            if (items.length === 0) document.title = document.title.replace(/^🔔 \d+ بانتظار الدخول — /, '');
            lastCount = items.length;
        };

        const poll = async () => {
            const r = await api(root.dataset.waitingUrl);
            if (r.ok) render(r.data.waiting || []);
        };

        document.getElementById('admitAll').addEventListener('click', async () => {
            await api(root.dataset.decideUrl, 'POST', { action: 'admit_all' });
            poll();
        });
        toggle.addEventListener('change', async () => {
            await api(root.dataset.lobbyUrl, 'POST', { enabled: toggle.checked });
            poll();
        });

        poll();
        setInterval(poll, 4000);
    }

    document.getElementById('roomJoin').addEventListener('click', () => {
        if (isHost) join(root.dataset.token, root.dataset.url);
        else requestEntry();
    });
    window.addEventListener('beforeunload', () => { leaving = true; if (room) room.disconnect(); });
}
