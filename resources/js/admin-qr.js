import QRCode from 'qrcode';

const canvas = document.getElementById('qr');
if (canvas) {
    QRCode.toCanvas(canvas, canvas.dataset.uri, { width: 180, margin: 0 });
}
