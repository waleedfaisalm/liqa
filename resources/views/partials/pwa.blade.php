<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ __(\App\Models\Setting::site('name')) }}">
<script>
    window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); window.__pwaPrompt = e; document.dispatchEvent(new Event('pwa-ready')); });
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
    }
</script>
