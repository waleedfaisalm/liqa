import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

// رسائل النجاح تختفي لوحدها بعد ٦ ثواني
document.querySelectorAll('.js-flash').forEach((el) => {
    setTimeout(() => {
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 6000);
});
