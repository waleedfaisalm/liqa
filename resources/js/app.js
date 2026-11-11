import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

// رسائل النجاح تختفي لوحدها بعد ٦ ثواني
document.querySelectorAll('.js-flash').forEach((el) => {
    setTimeout(() => {
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 6000);
});

// منع الإرسال المزدوج: بعد إرسال أي نموذج تُعطَّل أزرار الإرسال فيه لتفادي تكرار الحدث أو الطلب
document.addEventListener('submit', (e) => {
    if (e.defaultPrevented) return;
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.submitting === '1') {
        e.preventDefault();
        return;
    }
    form.dataset.submitting = '1';
    setTimeout(() => {
        form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]').forEach((b) => {
            b.disabled = true;
            b.classList.add('is-loading');
        });
    }, 0);
});

// عند الرجوع بزر المتصفح تعود النماذج قابلة للإرسال
window.addEventListener('pageshow', (e) => {
    if (!e.persisted) return;
    document.querySelectorAll('form[data-submitting="1"]').forEach((f) => {
        delete f.dataset.submitting;
        f.querySelectorAll('button').forEach((b) => { b.disabled = false; b.classList.remove('is-loading'); });
    });
});
