/**
 * NM-DigitalHUB Laravel Bootstrap
 * 
 * זהו קובץ אתחול בסיסי ליישום JS שלך.
 * הוא טוען את ההגדרות המשותפות, Axios, CSRF, ו־Echo אם נדרש.
 */

import _ from 'lodash'
window._ = _

// =========================
// 📦 Axios – HTTP Client
// =========================
import axios from 'axios'
window.axios = axios

// הגדרת כותרת ברירת מחדל לכל הבקשות
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// =========================
// 🔒 CSRF Token Injection
// =========================
const token = document.head.querySelector('meta[name="csrf-token"]')

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
} else {
    console.warn('⚠️ CSRF token not found: ensure <meta name="csrf-token"> exists in <head>.')
}

// =========================
// 📡 Echo (Laravel Reverb - WebSocket Broadcasting)
// =========================
/**
 * Laravel Reverb - Modern WebSocket Server for Laravel
 * Port: 8082 | Scheme: HTTPS
 */
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
})

if (import.meta.env.DEV) {
    console.log('📡 Laravel Echo connected via Reverb')
    console.log('   Host:', import.meta.env.VITE_REVERB_HOST)
    console.log('   Port:', import.meta.env.VITE_REVERB_PORT)
    console.log('   Scheme:', import.meta.env.VITE_REVERB_SCHEME)
}

// =========================
// 🧩 הודעות Debug (במצב פיתוח בלבד)
// =========================
if (import.meta.env.DEV) {
    console.log('🚀 bootstrap.js loaded successfully (Vite + Axios ready)')
}
/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */


