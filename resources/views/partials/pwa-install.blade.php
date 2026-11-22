<span class="small" data-pwa-wrap hidden>
    <button type="button" class="btn btn-liqa-outline btn-sm px-3" data-pwa-install>{{ __('ثبّت التطبيق') }}</button>
    <span class="d-block hint mt-1" data-pwa-ios hidden>{{ __('على آيفون: اضغط زر المشاركة ثم «إضافة إلى الشاشة الرئيسية».') }}</span>
</span>
<script>
(function () {
    var wrap = document.querySelector('[data-pwa-wrap]');
    if (!wrap) { return; }
    var standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone;
    if (standalone) { return; }
    var btn = wrap.querySelector('[data-pwa-install]');
    var ios = wrap.querySelector('[data-pwa-ios]');
    var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
    function show() { wrap.hidden = false; }
    if (window.__pwaPrompt) { show(); }
    document.addEventListener('pwa-ready', show);
    if (isIos) { show(); }
    btn.addEventListener('click', function () {
        if (window.__pwaPrompt) {
            window.__pwaPrompt.prompt();
            window.__pwaPrompt.userChoice.then(function () { window.__pwaPrompt = null; wrap.hidden = true; });
        } else if (isIos) { ios.hidden = !ios.hidden; }
    });
    window.addEventListener('appinstalled', function () { wrap.hidden = true; });
})();
</script>
