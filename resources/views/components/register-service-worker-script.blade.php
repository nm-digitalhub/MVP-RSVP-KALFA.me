@props([
    'script' => 'sw.js',
])

{{-- Register service worker only in the browser shell, not inside the NativePHP app runtime --}}
@web
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(@js(asset($script))).catch(function () {});
        });
    }
</script>
@endweb
