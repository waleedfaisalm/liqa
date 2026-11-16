import { Room, RoomEvent, Track, DisconnectReason } from 'livekit-client';

const root = document.getElementById('room');
const DICT = window.LIQA_T || {};
const tr = (key, params) => {
    let out = DICT[key] || key;
    if (params) for (const k in params) out = out.split(':' + k).join(params[k]);
    return out;
};
if (root) {
    const grid = document.getElementById('roomGrid');
    const audios = document.getElementById('roomAudios');
    const prejoin = document.getElementById('roomPrejoin');
    const stage = document.getElementById('roomStage');
    const errBox = document.getElementById('roomError');
    const countEl = document.getElementById('roomCount');
    const isOwner = root.dataset.host === '1';
    let canManage = isOwner || root.dataset.cohost === '1'; // المنظّم أو المنظّم المساعد

    const ICONS = {
        mic: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="3" width="6" height="12" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/></svg>',
        micOff: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M9 9v2a3 3 0 0 0 5 2.2M15 10V6a3 3 0 0 0-5.8-1M5 11a7 7 0 0 0 11 5.7M19 11a7 7 0 0 1-.6 2.8M12 18v3"/></svg>',
        cam: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="13" height="12" rx="2"/><path d="M16 10l5-3v10l-5-3"/></svg>',
        camOff: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M7 6h7a2 2 0 0 1 2 2v3m0 3v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 .6-1.4M16 10l5-3v10l-3-1.8"/></svg>',
        share: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4M9 10l3-3 3 3M12 7v6"/></svg>',
        leave: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h5M15 8l4 4-4 4M19 12H9"/></svg>',
        chat: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v11H9l-5 4z"/></svg>',
        hand: '<span class="room-emoji">✋</span>',
        react: '<span class="room-emoji">🙂</span>',
        people: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M3 20a6 6 0 0 1 12 0M16 5a3 3 0 0 1 0 6M18 14a5.5 5.5 0 0 1 3 5"/></svg>',
        manage: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>',
        pin: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4l5 5-3 1-3.5 3.5.5 4-1.5 1.5-3.5-3.5L5 20l-1-1 4.5-4.5L5 11l1.5-1.5 4 .5L14 6.5z"/></svg>',
        kick: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>',
    };
    document.getElementById('icoMic').innerHTML = ICONS.mic;
    document.getElementById('icoCam').innerHTML = ICONS.cam;
    document.getElementById('icoShare').innerHTML = ICONS.share;
    document.getElementById('icoLeave').innerHTML = ICONS.leave;
    document.getElementById('icoChat').innerHTML = ICONS.chat;
    document.getElementById('icoHand').innerHTML = ICONS.hand;
    document.getElementById('icoReact').innerHTML = ICONS.react;
    document.getElementById('icoPeople').innerHTML = ICONS.people;
    document.getElementById('icoManage').innerHTML = ICONS.manage;

    const meta = (p) => {
        try { return JSON.parse(p.metadata || '{}'); } catch { return {}; }
    };
    const hostLike = (p) => { const m = meta(p); return !!(m.host || m.cohost); };
    const el = (tag, cls, text) => { const e = document.createElement(tag); if (cls) e.className = cls; if (text) e.textContent = text; return e; };
    let selfMuteUntil = 0;   // لتمييز كتم المنظّم عن كتمك أنت
    let syncControls = () => {};
    let myHand = 0;          // وقت رفع يدك (0 = منخفضة)
    const prevHands = new Map();

    // إشعار صغير أعلى الشاشة
    function toast(text) {
        const box = document.getElementById('roomToasts');
        if (!box) return;
        const t = el('div', 'room-toast', text);
        box.appendChild(t);
        setTimeout(() => t.classList.add('is-out'), 3200);
        setTimeout(() => t.remove(), 3700);
        while (box.children.length > 4) box.firstChild.remove();
    }

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

    function showError(msg) {
        errBox.textContent = msg;
        errBox.classList.remove('d-none');
    }


    // ---- توزيع البطاقات (مثل Meet/Zoom): تملأ الشاشة، ومشاركة الشاشة أو التثبيت يكبّر بطاقة ----
    let pinnedId = null;   // تثبيتك الشخصي
    let hostSpot = null;   // تثبيت المنظّم للجميع
    let layoutFrame = 0;

    function scheduleLayout() {
        if (layoutFrame) return;
        layoutFrame = requestAnimationFrame(() => { layoutFrame = 0; layoutGrid(); });
    }

    function pickSpotlight() {
        if (pinnedId && tiles.has(pinnedId)) return pinnedId;
        if (hostSpot && tiles.has(hostSpot)) return hostSpot;
        for (const [id, t] of tiles) if (t.el.classList.contains('is-screen')) return id;
        return null;
    }

    function layoutGrid() {
        const n = tiles.size;
        if (!n) return;

        const spot = n > 1 ? pickSpotlight() : null;
        grid.classList.toggle('has-spot', !!spot);
        tiles.forEach((t, id) => {
            t.el.classList.toggle('is-spot', id === spot);
            t.pinBtn.classList.toggle('is-on', id === pinnedId);
        });
        if (spot) return;

        const W = grid.clientWidth;
        const H = grid.clientHeight;
        if (!W || !H) return;
        const gap = 8;
        let best = { cols: 1, area: -1 };
        for (let cols = 1; cols <= n; cols++) {
            const rows = Math.ceil(n / cols);
            const w = (W - (cols - 1) * gap) / cols;
            const h = (H - (rows - 1) * gap) / rows;
            const tw = Math.min(w, (h * 16) / 9);
            const area = tw * ((tw * 9) / 16);
            if (area > best.area + 1) best = { cols, rows, area };
        }
        grid.style.setProperty('--cols', String(best.cols));
        grid.style.setProperty('--rows', String(Math.ceil(n / best.cols)));
    }

    function togglePin(id) {
        pinnedId = pinnedId === id ? null : id;
        scheduleLayout();
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
        nameEl.textContent = (p.name || tr('عضو')) + (p.isLocal ? tr(' (أنت)') : '');
        const micEl = document.createElement('span');
        micEl.className = 'room-tile__mic';
        const badge = document.createElement('span');
        badge.className = 'room-tile__host d-none';
        label.append(micEl, nameEl, badge);

        const handEl = document.createElement('span');
        handEl.className = 'room-tile__hand d-none';
        handEl.textContent = '✋';

        el.append(avatar, video, label, handEl);

        const pinBtn = document.createElement('button');
        pinBtn.type = 'button';
        pinBtn.className = 'room-tile__pin';
        pinBtn.title = tr('تثبيت / إلغاء التثبيت');
        pinBtn.innerHTML = ICONS.pin;
        pinBtn.addEventListener('click', () => togglePin(p.identity));
        el.appendChild(pinBtn);
        el.addEventListener('dblclick', (e) => { if (!e.target.closest('button')) togglePin(p.identity); });

        if (!p.isLocal) {
            const kick = document.createElement('button');
            kick.type = 'button';
            kick.className = 'room-tile__kick';
            kick.title = tr('إخراج من الغرفة');
            kick.innerHTML = ICONS.kick;
            kick.addEventListener('click', () => removeParticipant(p, kick));
            el.appendChild(kick);
        }

        grid.appendChild(el);
        t = { el, video, micEl, avatar, badge, handEl, pinBtn };
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

        const m = meta(p);
        t.el.classList.toggle('is-host-tile', !!m.host);
        t.el.classList.toggle('is-cohost-tile', !!m.cohost);
        t.badge.textContent = m.host ? tr('المنظّم') : tr('منظّم مساعد');
        t.badge.classList.toggle('d-none', !(m.host || m.cohost));

        const up = handAt(p) > 0;
        t.handEl.classList.toggle('d-none', !up);
        t.el.classList.toggle('has-hand', up);

        schedulePeople();
        scheduleLayout();
    }

    function handAt(p) {
        if (p.isLocal) return myHand;
        return parseInt((p.attributes && p.attributes.hand) || '0', 10) || 0;
    }

    function remove(p) {
        const t = tiles.get(p.identity);
        if (t) {
            t.el.remove();
            tiles.delete(p.identity);
            updateLayout();
        }
        prevHands.delete(p.identity);
        if (pinnedId === p.identity) pinnedId = null;
        if (hostSpot === p.identity) hostSpot = null;
        schedulePeople();
        scheduleLayout();
    }

    function updateLayout() {
        const n = tiles.size;
        grid.dataset.count = String(Math.min(n, 9));
        countEl.textContent = tr(':v1 في الغرفة', { v1: n });
        countEl.classList.toggle('d-none', n === 0);
        schedulePeople();
        scheduleLayout();
    }

    async function removeParticipant(p, btn) {
        if (!confirm(tr('إخراج :v1 من الغرفة؟ لن يستطيع الدخول مجددًا.', { v1: (p.name || tr('هذا العضو')) }))) return;
        btn.disabled = true;
        const r = await api(root.dataset.removeUrl, 'POST', { identity: p.identity });
        if (!r.ok) toast(r.data.message || tr('تعذّر إخراج العضو.'));
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
            selfMuteUntil = Date.now() + 1500;
            try { await lp.setMicrophoneEnabled(!lp.isMicrophoneEnabled); } catch (e) { alert(tr('تعذّر الوصول إلى الميكروفون. تأكد من منح الإذن للمتصفح.')); }
            sync();
        });
        btnCam.addEventListener('click', async () => {
            try { await lp.setCameraEnabled(!lp.isCameraEnabled); } catch (e) { alert(tr('تعذّر الوصول إلى الكاميرا. تأكد من منح الإذن للمتصفح.')); }
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
        syncControls = sync;
        sync();

        return sync;
    }


    // ---- المحادثة النصية (عبر قناة بيانات LiveKit، لا تُحفظ) ----
    let chatLocked = false; // يمنع المشاركين من الكتابة (يتحكم به المنظّم)
    let chatOpen = false;
    let unread = 0;
    let lastSent = 0;
    const enc = new TextEncoder();
    const dec = new TextDecoder();

    function setupChat() {
        const panel = document.getElementById('roomChat');
        const list = document.getElementById('chatList');
        const empty = document.getElementById('chatEmpty');
        const form = document.getElementById('chatForm');
        const input = document.getElementById('chatInput');
        const dot = document.getElementById('chatDot');
        const btn = document.getElementById('btnChat');

        const time = () => new Date().toLocaleTimeString('ar', { hour: 'numeric', minute: '2-digit' });

        const refreshLock = () => {
            const blocked = chatLocked && !canManage;
            input.disabled = blocked;
            document.getElementById('chatSend').disabled = blocked;
            input.placeholder = blocked ? tr('أوقف المنظّم الكتابة في المحادثة') : tr('اكتب رسالة…');
        };

        const addMessage = (name, text, mine, role) => {
            if (empty) empty.remove();
            const row = document.createElement('div');
            row.className = 'chat-msg' + (mine ? ' is-mine' : '');
            const head = document.createElement('div');
            head.className = 'chat-msg__head';
            head.textContent = (mine ? tr('أنت') : name) + (role ? ' · ' + role : '') + ' · ' + time();
            const body = document.createElement('div');
            body.className = 'chat-msg__body';
            body.textContent = text; // نص خام فقط، بدون HTML
            row.append(head, body);
            list.appendChild(row);
            list.scrollTop = list.scrollHeight;
        };

        const setOpen = (v) => {
            chatOpen = v;
            panel.classList.toggle('d-none', !v);
            if (v) { unread = 0; dot.classList.add('d-none'); setTimeout(() => input.focus(), 50); list.scrollTop = list.scrollHeight; }
        };

        btn.addEventListener('click', () => setOpen(!chatOpen));
        document.getElementById('chatClose').addEventListener('click', () => setOpen(false));

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            const text = input.value.trim().slice(0, 500);
            if (!text || (chatLocked && !canManage)) return;
            if (Date.now() - lastSent < 400) return; // حدّ بسيط ضد الإغراق
            lastSent = Date.now();
            input.value = '';
            try {
                await room.localParticipant.publishData(enc.encode(JSON.stringify({ t: text })), { reliable: true, topic: 'chat' });
                addMessage(room.localParticipant.name, text, true, isOwner ? tr('المنظّم') : (canManage ? tr('منظّم مساعد') : ''));
            } catch (err) {
                addMessage(tr('النظام'), tr('تعذّر إرسال الرسالة.'), false, '');
            }
        });

        room.on(RoomEvent.DataReceived, (payload, participant, kind, topic) => {
            if (!participant) return;
            let data;
            try { data = JSON.parse(dec.decode(payload)); } catch (e) { return; }
            const fromHost = hostLike(participant); // البيانات الوصفية من الخادم ولا يمكن تزويرها
            const roleOf = (pp) => (meta(pp).host ? tr('المنظّم') : (meta(pp).cohost ? tr('منظّم مساعد') : ''));

            if (topic === 'control') {
                if (fromHost && typeof data.chatLocked === 'boolean') { chatLocked = data.chatLocked; refreshLock(); }
                return;
            }
            if (topic !== 'chat' || typeof data.t !== 'string') return;
            if (chatLocked && !fromHost) return;

            addMessage(participant.name || tr('عضو'), data.t.slice(0, 500), false, roleOf(participant));
            if (!chatOpen) {
                unread += 1;
                dot.textContent = String(unread);
                dot.classList.remove('d-none');
            }
        });

        // المنظّم: مفتاح السماح بالكتابة، ويُعلَن للقادمين الجدد
        if (isOwner) {
            const toggle = document.getElementById('chatToggle');
            const broadcast = () => room.localParticipant
                .publishData(enc.encode(JSON.stringify({ chatLocked })), { reliable: true, topic: 'control' }).catch(() => {});
            toggle.addEventListener('change', () => { chatLocked = !toggle.checked; broadcast(); });
            room.on(RoomEvent.ParticipantConnected, () => { if (chatLocked) setTimeout(broadcast, 800); });
        }

        refreshLock();
    }


    // ---- المشاركون (قائمة جانبية) ----
    let peopleOpen = false;
    let peopleFrame = 0;

    const everyone = () => (room ? [room.localParticipant, ...room.remoteParticipants.values()] : []);

    function schedulePeople() {
        if (peopleFrame) return;
        peopleFrame = requestAnimationFrame(() => { peopleFrame = 0; renderPeople(); });
    }

    function micPub(p) { return p.getTrackPublication(Track.Source.Microphone); }

    async function muteTargets(list) {
        const targets = list
            .map((p) => ({ identity: p.identity, pub: micPub(p) }))
            .filter((x) => x.pub && x.pub.trackSid && !x.pub.isMuted)
            .map((x) => ({ identity: x.identity, sid: x.pub.trackSid }));
        if (!targets.length) { toast(tr('لا توجد ميكروفونات مفتوحة لكتمها.')); return; }
        const r = await api(root.dataset.muteUrl, 'POST', { targets });
        toast(r.ok ? tr('تم كتم :v1 ميكروفون.', { v1: (r.data.muted || 0) }) : tr('تعذّر الكتم. أعد المحاولة.'));
    }

    function sendControl(obj, dest) {
        const opts = { reliable: true, topic: 'control' };
        if (dest) opts.destinationIdentities = dest;
        return room.localParticipant.publishData(enc.encode(JSON.stringify(obj)), opts).catch(() => {});
    }

    function renderPeople() {
        if (!room) return;
        const all = everyone();
        document.getElementById('peopleTotal').textContent = String(all.length);

        const hands = all.filter((p) => handAt(p) > 0);
        const dot = document.getElementById('handDot');
        dot.textContent = String(hands.length);
        dot.classList.toggle('d-none', !(canManage && hands.length > 0));

        if (!peopleOpen) return;

        const rank = (p) => {
            const m = meta(p);
            return (m.host ? 0 : 1000) + (m.cohost ? 0 : 500) + (handAt(p) > 0 ? 0 : 100);
        };
        all.sort((a, b) => (rank(a) - rank(b)) || ((handAt(a) || 0) - (handAt(b) || 0)) || String(a.name).localeCompare(String(b.name), 'ar'));

        const list = document.getElementById('peopleList');
        list.innerHTML = '';

        all.forEach((p) => {
            const m = meta(p);
            const row = el('div', 'people-row');

            const av = el('span', 'wait-row__avatar');
            if (m.avatar) { const im = document.createElement('img'); im.src = m.avatar; im.alt = ''; av.appendChild(im); } else { av.textContent = m.initial || (p.name || '?').charAt(0); }

            const info = el('div', 'people-row__info');
            info.appendChild(el('div', 'people-row__name', (p.name || tr('عضو')) + (p.isLocal ? tr(' (أنت)') : '')));
            const role = m.host ? tr('المنظّم') : (m.cohost ? tr('منظّم مساعد') : '');
            if (role) info.appendChild(el('div', 'people-row__role', role));

            const state = el('span', 'people-row__state');
            if (handAt(p) > 0) state.appendChild(el('span', 'people-row__hand', '✋'));
            const mp = micPub(p);
            const micOn = !!(mp && mp.track && !mp.isMuted);
            const mic = el('span', 'people-row__mic' + (micOn ? '' : ' is-off'));
            mic.innerHTML = micOn ? ICONS.mic.replace(/width="22" height="22"/, 'width="16" height="16"') : ICONS.micOff.replace(/width="22" height="22"/, 'width="16" height="16"');
            state.appendChild(mic);

            row.append(av, info, state);

            if (canManage) {
                const acts = el('div', 'people-row__acts');
                const small = (txt, fn) => { const b = el('button', 'btn btn-sm btn-liqa-outline', txt); b.type = 'button'; b.addEventListener('click', async () => { b.disabled = true; await fn(); b.disabled = false; }); return b; };

                const spotted = hostSpot === p.identity;
                acts.appendChild(small(spotted ? tr('إلغاء التثبيت للجميع') : tr('تثبيت للجميع'), () => setHostSpot(spotted ? null : p.identity)));
                row.appendChild(acts);
            }

            if (canManage && !p.isLocal && !m.host) {
                const acts = row.querySelector('.people-row__acts') || el('div', 'people-row__acts');
                const small = (txt, fn) => { const b = el('button', 'btn btn-sm btn-liqa-outline', txt); b.type = 'button'; b.addEventListener('click', async () => { b.disabled = true; await fn(); b.disabled = false; }); return b; };

                if (handAt(p) > 0) acts.appendChild(small(tr('إنزال اليد'), () => sendControl({ lower: p.identity }, [p.identity])));
                if (micOn && (isOwner || !m.cohost)) acts.appendChild(small(tr('كتم'), () => muteTargets([p])));
                if (isOwner) {
                    acts.appendChild(small(m.cohost ? tr('إلغاء المساعد') : tr('منظّم مساعد'), async () => {
                        const r = await api(root.dataset.cohostUrl, 'POST', { identity: p.identity, on: !m.cohost });
                        toast(r.ok ? (m.cohost ? tr('أُلغيت صلاحية المساعد.') : tr('أصبح :v1 منظّمًا مساعدًا.', { v1: (p.name || tr('العضو')) })) : (r.data.message || tr('تعذّر التعديل.')));
                    }));
                }
                if (isOwner || !m.cohost) {
                    const k = small(tr('إخراج'), () => removeParticipant(p, k));
                    k.classList.add('is-danger');
                    acts.appendChild(k);
                }
                if (!acts.parentNode) row.appendChild(acts);
            }
            list.appendChild(row);
        });
    }

    function setupPeople() {
        const panel = document.getElementById('roomPeople');
        const chatPanel = document.getElementById('roomChat');
        const btn = document.getElementById('btnPeople');
        const setOpen = (v) => {
            peopleOpen = v;
            panel.classList.toggle('d-none', !v);
            if (v) { chatPanel.classList.add('d-none'); chatOpen = false; renderPeople(); }
        };
        btn.addEventListener('click', () => setOpen(!peopleOpen));
        document.getElementById('peopleClose').addEventListener('click', () => setOpen(false));
        document.getElementById('btnChat').addEventListener('click', () => { if (chatOpen) setOpen(false); });

        document.getElementById('btnManage').addEventListener('click', () => document.getElementById('roomLobbyBar').classList.toggle('is-open'));
        document.getElementById('muteAll').addEventListener('click', () => {
            if (!confirm(tr('كتم ميكروفونات جميع المشاركين؟ يستطيع كل منهم تشغيله من جديد.'))) return;
            muteTargets([...room.remoteParticipants.values()].filter((p) => !hostLike(p) || isOwner).filter((p) => !meta(p).host));
        });
        document.getElementById('lowerAll').addEventListener('click', () => {
            sendControl({ lower: 'all' });
            toast(tr('أُنزلت الأيدي المرفوعة.'));
        });
    }

    // ---- رفع اليد (خصائص المشارك، فيراها حتى القادمون لاحقًا) ----
    function setupHand() {
        const btn = document.getElementById('btnHand');
        const apply = async (up) => {
            myHand = up ? Date.now() : 0;
            btn.classList.toggle('is-active', up);
            refresh(room.localParticipant);
            try { await room.localParticipant.setAttributes({ hand: up ? String(myHand) : '' }); } catch (e) { toast(tr('تعذّر تحديث رفع اليد.')); }
        };
        btn.addEventListener('click', () => apply(!myHand));

        room.on(RoomEvent.ParticipantAttributesChanged, (changed, p) => {
            if (p.isLocal) return;
            refresh(p);
            const up = handAt(p) > 0;
            if (up && !prevHands.get(p.identity) && canManage) toast(tr('✋ :v1 رفع يده', { v1: (p.name || tr('عضو')) }));
            prevHands.set(p.identity, up);
        });

        room.on(RoomEvent.DataReceived, (payload, participant, kind, topic) => {
            if (topic !== 'control' || !participant || !hostLike(participant)) return;
            let d; try { d = JSON.parse(dec.decode(payload)); } catch (e) { return; }
            if (d && d.lower && myHand && (d.lower === 'all' || d.lower === room.localParticipant.identity)) {
                apply(false);
                toast(tr('أنزل المنظّم يدك.'));
            }
        });
    }

    // ---- ردود الفعل السريعة ----
    const REACTIONS = ['👍', '👏', '❤️', '😂', '🎉'];
    function setupReactions() {
        const bar = document.getElementById('reactBar');
        const btn = document.getElementById('btnReact');
        const layer = document.getElementById('roomReacts');
        let last = 0;

        const spawn = (emoji, name) => {
            const f = el('div', 'react-float');
            f.style.insetInlineStart = (8 + Math.random() * 80) + '%';
            f.appendChild(el('span', 'react-float__emoji', emoji));
            if (name) f.appendChild(el('span', 'react-float__name', name));
            layer.appendChild(f);
            setTimeout(() => f.remove(), 3200);
            while (layer.children.length > 25) layer.firstChild.remove();
        };

        btn.addEventListener('click', (e) => { e.stopPropagation(); bar.classList.toggle('d-none'); });
        document.addEventListener('click', (e) => { if (!bar.contains(e.target)) bar.classList.add('d-none'); });

        bar.querySelectorAll('button').forEach((b) => b.addEventListener('click', () => {
            if (Date.now() - last < 300) return;
            last = Date.now();
            const emoji = b.dataset.emoji;
            spawn(emoji, tr('أنت'));
            room.localParticipant.publishData(enc.encode(JSON.stringify({ e: emoji })), { reliable: false, topic: 'react' }).catch(() => {});
        }));

        room.on(RoomEvent.DataReceived, (payload, participant, kind, topic) => {
            if (topic !== 'react' || !participant) return;
            let d; try { d = JSON.parse(dec.decode(payload)); } catch (e) { return; }
            if (d && REACTIONS.includes(d.e)) spawn(d.e, participant.name || '');
        });
    }

    // ---- المؤقّت ونسخ الرابط ----
    function startTimer() {
        const t = document.getElementById('roomTimer');
        const t0 = Date.now();
        t.classList.remove('d-none');
        const pad = (n) => String(n).padStart(2, '0');
        setInterval(() => {
            const s = Math.floor((Date.now() - t0) / 1000);
            const h = Math.floor(s / 3600);
            t.textContent = (h ? h + ':' : '') + pad(Math.floor((s % 3600) / 60)) + ':' + pad(s % 60);
        }, 1000);
    }

    // ---- صلاحيات المنظّم / المنظّم المساعد ----
    let hostPanelStarted = false;
    function setManager(v) {
        canManage = v;
        root.classList.toggle('is-manager', v);
        if (v && !hostPanelStarted) { hostPanelStarted = true; startHostPanel(); }
        if (isOwner) document.getElementById('roomLobbyBar').classList.remove('d-none');
        everyone().forEach((p) => refresh(p));
        schedulePeople();
    }


    // ---- تثبيت المنظّم للجميع + إنهاء الاجتماع ----
    function setHostSpot(id) {
        hostSpot = id;
        scheduleLayout();
        sendControl({ spot: id });
    }

    function setupControlHandlers() {
        room.on(RoomEvent.DataReceived, (payload, participant, kind, topic) => {
            if (topic !== 'control' || !participant || !hostLike(participant)) return;
            let d; try { d = JSON.parse(dec.decode(payload)); } catch (e) { return; }
            if (!d) return;

            if ('spot' in d && (d.spot === null || typeof d.spot === 'string')) {
                hostSpot = d.spot;
                scheduleLayout();
                schedulePeople();
            }

            if (d.ended === true) {
                leaving = true;
                toast(tr('أنهى المنظّم الاجتماع.'));
                setTimeout(() => { try { room.disconnect(); } catch (e) { /* يُتجاهل */ } window.location.href = root.dataset.exit; }, 1800);
            }
        });

        // المنظّم يعيد إعلان التثبيت للقادمين الجدد
        room.on(RoomEvent.ParticipantConnected, () => { if (canManage && hostSpot) setTimeout(() => sendControl({ spot: hostSpot }), 800); });
    }

    async function endForAll() {
        if (!confirm(tr('إنهاء الاجتماع للجميع؟ سيُفصل كل الحاضرين الآن.'))) return;
        await sendControl({ ended: true });
        await new Promise((r) => setTimeout(r, 600));
        await api(root.dataset.endUrl, 'POST', {});
        leaving = true;
        try { room.disconnect(); } catch (e) { /* يُتجاهل */ }
        window.location.href = root.dataset.exit;
    }

    // ---- قوائم منبثقة: اختيار الأجهزة والمغادرة ----
    function placePop(pop, anchor) {
        const r = anchor.getBoundingClientRect();
        pop.classList.remove('d-none');
        const w = pop.offsetWidth;
        const left = Math.min(Math.max(8, r.left + r.width / 2 - w / 2), window.innerWidth - w - 8);
        pop.style.left = left + 'px';
    }

    function setupPopMenus() {
        const devMenu = document.getElementById('devMenu');
        const leaveMenu = document.getElementById('leaveMenu');
        const closeAll = () => { devMenu.classList.add('d-none'); leaveMenu.classList.add('d-none'); };

        async function openDevices(kind, anchor) {
            if (!devMenu.classList.contains('d-none') && devMenu.dataset.kind === kind) { closeAll(); return; }
            closeAll();
            devMenu.dataset.kind = kind;
            devMenu.innerHTML = '';
            const sections = kind === 'audioinput'
                ? [['audioinput', tr('الميكروفون')], ['audiooutput', tr('مخرج الصوت')]]
                : [['videoinput', tr('الكاميرا')]];

            for (const [k, title] of sections) {
                let list = [];
                try { list = await Room.getLocalDevices(k); } catch (e) { /* لا إذن */ }
                devMenu.appendChild(el('div', 'room-pop__title', title));
                if (!list.length) { devMenu.appendChild(el('div', 'room-pop__empty', k === 'audiooutput' ? tr('غير مدعوم في هذا المتصفح') : tr('لا توجد أجهزة'))); continue; }
                const active = room.getActiveDevice(k);
                list.forEach((d, i) => {
                    const b = el('button', 'room-pop__item' + (d.deviceId === active ? ' is-active' : ''), d.label || (title + ' ' + (i + 1)));
                    b.type = 'button';
                    b.addEventListener('click', async () => {
                        try { await room.switchActiveDevice(k, d.deviceId); toast(tr('تم تغيير الجهاز.')); } catch (e) { toast(tr('تعذّر تغيير الجهاز.')); }
                        closeAll();
                    });
                    devMenu.appendChild(b);
                });
            }
            placePop(devMenu, anchor);
        }

        document.getElementById('caretMic').addEventListener('click', (e) => { e.stopPropagation(); openDevices('audioinput', e.currentTarget); });
        document.getElementById('caretCam').addEventListener('click', (e) => { e.stopPropagation(); openDevices('videoinput', e.currentTarget); });

        // زر المغادرة: للمنظّم خياران (مغادرة فقط / إنهاء للجميع)
        if (isOwner) {
            const btn = document.getElementById('btnLeave');
            btn.addEventListener('click', (e) => {
                e.stopImmediatePropagation();
                if (!leaveMenu.classList.contains('d-none')) { closeAll(); return; }
                closeAll();
                placePop(leaveMenu, btn);
            }, true);
            document.getElementById('leaveOnly').addEventListener('click', () => {
                closeAll();
                leaving = true;
                room.disconnect();
                window.location.href = root.dataset.exit;
            });
            document.getElementById('endAll').addEventListener('click', () => { closeAll(); endForAll(); });
        }

        document.addEventListener('click', (e) => {
            if (!devMenu.contains(e.target) && !leaveMenu.contains(e.target)) closeAll();
        });
    }

    // ---- ما قبل الدخول: اختبار الأجهزة واختيارها ----
    const chosen = { audio: null, video: null };
    let previewStream = null;
    let previewCtx = null;
    let previewRaf = 0;

    function stopPreview() {
        if (previewRaf) { cancelAnimationFrame(previewRaf); previewRaf = 0; }
        if (previewCtx) { previewCtx.close().catch(() => {}); previewCtx = null; }
        if (previewStream) { previewStream.getTracks().forEach((t) => t.stop()); previewStream = null; }
        const v = document.getElementById('preView');
        if (v) v.srcObject = null;
    }

    function setupPrejoin() {
        const testBtn = document.getElementById('preTest');
        const box = document.getElementById('preBox');
        const view = document.getElementById('preView');
        const level = document.getElementById('preLevel');
        const selMic = document.getElementById('selMic');
        const selCam = document.getElementById('selCam');
        if (!testBtn || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { if (testBtn) testBtn.hidden = true; return; }

        const fill = (sel, list, kind, current) => {
            sel.innerHTML = '';
            list.forEach((d, i) => {
                const o = document.createElement('option');
                o.value = d.deviceId;
                o.textContent = d.label || (kind + ' ' + (i + 1));
                if (d.deviceId === current) o.selected = true;
                sel.appendChild(o);
            });
            if (!list.length) { const o = document.createElement('option'); o.textContent = tr('لا توجد أجهزة'); sel.appendChild(o); sel.disabled = true; } else { sel.disabled = false; }
        };

        async function start() {
            stopPreview();
            const audio = chosen.audio ? { deviceId: { exact: chosen.audio } } : true;
            const video = chosen.video ? { deviceId: { exact: chosen.video } } : true;
            try {
                previewStream = await navigator.mediaDevices.getUserMedia({ audio, video });
            } catch (e) {
                try { previewStream = await navigator.mediaDevices.getUserMedia({ audio }); }
                catch (e2) { showError(tr('تعذّر الوصول إلى الميكروفون والكاميرا. تأكد من منح الإذن للمتصفح.')); return; }
            }
            box.classList.remove('d-none');
            view.srcObject = previewStream;
            view.classList.toggle('d-none', previewStream.getVideoTracks().length === 0);

            const devices = await navigator.mediaDevices.enumerateDevices();
            const at = previewStream.getAudioTracks()[0];
            const vt = previewStream.getVideoTracks()[0];
            fill(selMic, devices.filter((d) => d.kind === 'audioinput'), tr('ميكروفون'), at && at.getSettings().deviceId);
            fill(selCam, devices.filter((d) => d.kind === 'videoinput'), tr('كاميرا'), vt && vt.getSettings().deviceId);

            // مؤشر مستوى الصوت
            if (at && (window.AudioContext || window.webkitAudioContext)) {
                previewCtx = new (window.AudioContext || window.webkitAudioContext)();
                const an = previewCtx.createAnalyser();
                an.fftSize = 256;
                previewCtx.createMediaStreamSource(new MediaStream([at])).connect(an);
                const buf = new Uint8Array(an.frequencyBinCount);
                const tick = () => {
                    an.getByteFrequencyData(buf);
                    const avg = buf.reduce((a, b) => a + b, 0) / buf.length;
                    level.style.width = Math.min(100, Math.round(avg * 1.8)) + '%';
                    previewRaf = requestAnimationFrame(tick);
                };
                tick();
            }
        }

        testBtn.addEventListener('click', start);
        selMic.addEventListener('change', () => { chosen.audio = selMic.value; start(); });
        selCam.addEventListener('change', () => { chosen.video = selCam.value; start(); });
    }

    async function join(token, serverUrl) {
        const joinBtn = document.getElementById('roomJoin');
        joinBtn.disabled = true;
        errBox.classList.add('d-none');

        stopPreview();
        const forcedMute = root.dataset.muteJoin === '1' && !canManage;
        const wantMic = document.getElementById('preMic').checked && !forcedMute;
        const wantCam = document.getElementById('preCam').checked;

        const opts = { adaptiveStream: true, dynacast: true };
        if (chosen.audio) opts.audioCaptureDefaults = { deviceId: chosen.audio };
        if (chosen.video) opts.videoCaptureDefaults = { deviceId: chosen.video };
        room = new Room(opts);

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
            .on(RoomEvent.TrackMuted, (pub, p) => {
                refresh(p);
                if (p.isLocal) {
                    syncControls();
                    if (pub.source === Track.Source.Microphone && Date.now() > selfMuteUntil) toast(tr('تم كتم ميكروفونك من قِبل المنظّم. يمكنك تشغيله متى شئت.'));
                }
            })
            .on(RoomEvent.TrackUnmuted, (pub, p) => { refresh(p); if (p.isLocal) syncControls(); })
            .on(RoomEvent.ParticipantMetadataChanged, (prev, p) => {
                if (!p) return;
                refresh(p);
                if (p.isLocal) {
                    const was = canManage;
                    setManager(isOwner || !!meta(p).cohost);
                    if (!was && canManage) toast(tr('أصبحت منظّمًا مساعدًا: يمكنك قبول المنتظرين وإدارة الغرفة.'));
                    if (was && !canManage) toast(tr('أُلغيت صلاحية المنظّم المساعد.'));
                }
            })
            .on(RoomEvent.ActiveSpeakersChanged, (speakers) => {
                const ids = new Set(speakers.map((s) => s.identity));
                tiles.forEach((t, id) => t.el.classList.toggle('is-speaking', ids.has(id)));
            })
            .on(RoomEvent.Disconnected, (reason) => {
                if (leaving) return;
                const removed = reason === DisconnectReason.PARTICIPANT_REMOVED;
                const ended = reason === DisconnectReason.ROOM_DELETED;
                alert(removed ? tr('أخرجك المنظّم من الغرفة.') : (ended ? tr('أنهى المنظّم الاجتماع.') : tr('انقطع الاتصال بالغرفة.')));
                window.location.href = root.dataset.exit;
            });

        try {
            await room.connect(serverUrl, token);
            await room.startAudio();
        } catch (e) {
            joinBtn.disabled = false;
            document.getElementById('roomWait').classList.add('d-none');
            prejoin.classList.remove('d-none');
            showError(tr('تعذّر الاتصال بالغرفة. تحقق من اتصالك وأعد المحاولة.'));
            return;
        }

        prejoin.classList.add('d-none');
        document.getElementById('roomWait').classList.add('d-none');
        stage.classList.remove('d-none');
        root.classList.add('in-call');
        document.documentElement.classList.add('room-lock');
        if (window.ResizeObserver) new ResizeObserver(scheduleLayout).observe(grid);
        window.addEventListener('resize', scheduleLayout);
        scheduleLayout();
        if (isOwner) document.getElementById('roomLobbyBar').classList.remove('d-none');
        if (canManage && !hostPanelStarted) { hostPanelStarted = true; startHostPanel(); }
        startTimer();

        refresh(room.localParticipant);
        room.remoteParticipants.forEach((p) => {
            refresh(p);
            p.trackPublications.forEach((pub) => {
                if (pub.track && pub.kind === Track.Kind.Audio) audios.appendChild(pub.track.attach());
            });
        });
        const sync = wireControls();
        setupChat();
        setupPeople();
        setupHand();
        setupReactions();
        setupControlHandlers();
        setupPopMenus();
        root.classList.toggle('is-manager', canManage);
        if (forcedMute) toast(tr('دخلت بميكروفون مكتوم بطلب من المنظّم. يمكنك تشغيله متى شئت.'));

        try { if (wantMic) await room.localParticipant.setMicrophoneEnabled(true); } catch (e) { alert(tr('تعذّر الوصول إلى الميكروفون.')); }
        try { if (wantCam) await room.localParticipant.setCameraEnabled(true); } catch (e) { alert(tr('تعذّر الوصول إلى الكاميرا.')); }
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
            showWait(tr('تعذّر الدخول'), r.data.message || tr('لم يُقبل دخولك.'), true);
            return;
        }
        showWait(tr('جارٍ الدخول إلى الغرفة…'), tr('لحظات ويتم الاتصال.'));
        await join(r.data.token, r.data.url);
    }

    function handleStatus(status) {
        if (status === 'admitted') { stopPoll(); enterWithToken(); return true; }
        if (status === 'denied') { stopPoll(); showWait(tr('رفض المنظّم دخولك'), tr('لم يُسمح لك بدخول هذه الغرفة.'), true); return true; }
        if (status === 'removed') { stopPoll(); showWait(tr('أخرجك المنظّم'), tr('لا يمكنك دخول هذه الغرفة مجددًا.'), true); return true; }
        return false;
    }

    async function requestEntry() {
        const joinBtn = document.getElementById('roomJoin');
        joinBtn.disabled = true;
        errBox.classList.add('d-none');

        const r = await api(root.dataset.knockUrl, 'POST');
        if (!r.ok) {
            joinBtn.disabled = false;
            showError(r.data.message || tr('تعذّر إرسال الطلب. أعد المحاولة.'));
            return;
        }
        if (handleStatus(r.data.status)) return;

        showWait(tr('بانتظار موافقة المنظّم…'), tr('أُرسل طلبك، وستدخل الغرفة تلقائيًا بمجرد قبوله. أبقِ هذه الصفحة مفتوحة.'));
        pollTimer = setInterval(async () => {
            const s = await api(root.dataset.statusUrl);
            if (s.ok) handleStatus(s.data.status);
        }, 3000);
    }

    // ---- المنظّم: لوحة المنتظرين وغرفة الانتظار ----
    function startHostPanel() {
        const bar = document.getElementById('roomLobbyBar');
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
                nm.textContent = u.name + (u.link ? tr(' · عبر الرابط') : '');
                const ok = document.createElement('button');
                ok.type = 'button'; ok.className = 'btn btn-sm btn-liqa'; ok.textContent = tr('قبول');
                ok.addEventListener('click', async () => { ok.disabled = true; await api(root.dataset.decideUrl, 'POST', { action: 'admit', user_id: u.id }); poll(); });
                const no = document.createElement('button');
                no.type = 'button'; no.className = 'btn btn-sm btn-liqa-outline'; no.textContent = tr('رفض');
                no.addEventListener('click', async () => { no.disabled = true; await api(root.dataset.decideUrl, 'POST', { action: 'deny', user_id: u.id }); poll(); });
                row.append(av, nm, ok, no);
                list.appendChild(row);
            });
            count.textContent = String(items.length);
            panel.classList.toggle('d-none', items.length === 0);
            bar.classList.toggle('has-waiting', items.length > 0); // تفتح لوحة الإدارة تلقائيًا عند وجود منتظرين
            if (items.length > lastCount) {
                toast(tr('🔔 :v1 بانتظار الدخول', { v1: items.length }));
                try { document.title = tr('🔔 :v1 بانتظار الدخول — :v2', { v1: items.length, v2: document.title.replace(/^🔔 \d+ [^—]*— /, '') }); } catch (e) { /* يُتجاهل */ }
            }
            if (items.length === 0) document.title = document.title.replace(/^🔔 \d+ [^—]*— /, '');
            lastCount = items.length;
        };

        const poll = async () => {
            if (!canManage) return;
            const r = await api(root.dataset.waitingUrl);
            if (r.ok) render(r.data.waiting || []);
        };

        document.getElementById('admitAll').addEventListener('click', async () => {
            await api(root.dataset.decideUrl, 'POST', { action: 'admit_all' });
            poll();
        });

        if (isOwner) {
            toggle.addEventListener('change', async () => {
                await api(root.dataset.lobbyUrl, 'POST', { enabled: toggle.checked });
                poll();
            });
            document.getElementById('muteJoinToggle').addEventListener('change', async (e) => {
                const r = await api(root.dataset.mutejoinUrl, 'POST', { enabled: e.target.checked });
                root.dataset.muteJoin = e.target.checked ? '1' : '0';
                toast(r.ok ? (e.target.checked ? tr('سيدخل المشاركون الجدد بميكروفونات مكتومة.') : tr('أُلغي كتم الدخول.')) : tr('تعذّر الحفظ.'));
            });
            document.getElementById('linkToggle').addEventListener('change', async (e) => {
                const r = await api(root.dataset.linkUrl, 'POST', { enabled: e.target.checked });
                toast(r.ok ? (e.target.checked ? tr('فُعّل الدخول بالرابط.') : tr('أُوقف الدخول بالرابط.')) : tr('تعذّر الحفظ.'));
            });
            document.getElementById('copyLink').addEventListener('click', async () => {
                const url = root.dataset.roomLink;
                try { await navigator.clipboard.writeText(url); toast(tr('تم نسخ رابط الغرفة.')); }
                catch (e) { window.prompt(tr('انسخ الرابط:'), url); }
            });
        }

        poll();
        setInterval(poll, 4000);
    }

    setupPrejoin();
    if (root.dataset.muteJoin === '1' && !canManage) {
        document.getElementById('preMic').checked = false;
        document.getElementById('muteJoinNote').classList.remove('d-none');
    }

    document.getElementById('roomJoin').addEventListener('click', () => {
        stopPreview();
        if (isOwner) join(root.dataset.token, root.dataset.url);
        else requestEntry();
    });
    window.addEventListener('beforeunload', () => { leaving = true; if (room) room.disconnect(); });
}
