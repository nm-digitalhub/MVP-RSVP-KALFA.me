/**
 * Event SaaS App Entry — Alpine.js + Tailwind
 */

import './bootstrap'
import 'flowbite'

import $ from 'jquery'
window.$ = window.jQuery = $

import Cropper from 'cropperjs'
import Clipboard from '@ryangjchandler/alpine-clipboard'

// In Livewire v4, Alpine is automatically available.
// We just need to register plugins on the global Alpine instance.
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Clipboard)
})

document.addEventListener('DOMContentLoaded', () => {
    const image = document.getElementById('image')

    if (image) {
        new Cropper(image)
    }
})
