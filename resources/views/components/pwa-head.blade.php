@props([
    'manifest' => 'manifest.json',
    'themeColor' => '#000000',
])

{{-- Web PWA: manifest + install / theme hints (public/manifest.json, public/sw.js) --}}
<link rel="manifest" href="{{ asset($manifest) }}">
<meta name="theme-color" content="{{ $themeColor }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
