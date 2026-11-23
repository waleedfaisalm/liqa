import QRCode from 'qrcode';

const box = document.getElementById('checkin');
if (box) {
    const canvas = document.getElementById('checkinQr');
    const msg = document.getElementById('checkinMsg');
    const countEl = document.getElementById('checkinCount');

    async function drawQr() {
        try {
            const res = await fetch(box.dataset.qrUrl, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (data.open) {
                msg.classList.add('d-none');
                canvas.classList.remove('d-none');
                await QRCode.toCanvas(canvas, data.url, { width: 300, margin: 2, color: { dark: '#1a1033', light: '#ffffff' } });
            } else {
                canvas.classList.add('d-none');
                msg.textContent = data.message || '';
                msg.classList.remove('d-none');
            }
        } catch (e) { /* يُعاد المحاولة في الدورة التالية */ }
    }

    async function refreshStatus() {
        try {
            const res = await fetch(box.dataset.statusUrl, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            const ids = new Set((data.ids || []).map(String));
            let n = 0;
            document.querySelectorAll('[data-user-id]').forEach((row) => {
                const done = ids.has(row.dataset.userId);
                if (done) n++;
                row.querySelector('[data-done]').classList.toggle('d-none', !done);
                const manual = row.querySelector('[data-manual]');
                if (manual) manual.classList.toggle('d-none', done);
            });
            if (countEl) countEl.textContent = String(n);
        } catch (e) { /* تجاهل */ }
    }

    drawQr();
    refreshStatus();
    setInterval(drawQr, 20000);
    setInterval(refreshStatus, 8000);
}
