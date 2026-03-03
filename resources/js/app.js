/**
 * Event SaaS App Entry — Alpine.js + Tailwind
 */

import $ from 'jquery'
window.$ = window.jQuery = $

import './bootstrap';

import 'flowbite';

import Alpine from 'alpinejs'
window.Alpine = Alpine

// Livewire 3.x: Alpine is loaded; Livewire will start it.
window.__alpineAlreadyLoaded = true

if (!window.__alpineInitialized) {
    window.__alpineInitialized = true
    const originalWarn = console.warn
    console.warn = function (...args) {
        if (args[0]?.includes?.('multiple instances of Alpine')) return
        originalWarn.apply(console, args)
    }
    document.addEventListener('alpine:init', () => {
        if (import.meta.env.DEV) console.log('✅ Alpine.js initialized')
    })
    window.addEventListener('error', (e) => {
        if (e.message?.includes('Alpine')) console.error('❌ Alpine Error:', e.message)
    })
    if (import.meta.env.DEV) console.log('🟢 Alpine.js loaded (waiting for Livewire to start it)')
} else {
    if (import.meta.env.DEV) console.warn('⚠️ Alpine already initialized (HMR reload detected)')
}
