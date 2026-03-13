/**
 * Event SaaS App Entry — Alpine.js + Tailwind
 */

import './bootstrap'
import 'flowbite'

import Webpass from '@laragear/webpass'
import $ from 'jquery'
window.$ = window.jQuery = $

import Cropper from 'cropperjs'
import Clipboard from '@ryangjchandler/alpine-clipboard'
import intersect from '@alpinejs/intersect'
import collapse from '@alpinejs/collapse'

import Sortable from 'sortablejs'
import Chart from 'chart.js/auto'
import { computePosition, flip, shift, offset } from '@floating-ui/dom'

window.Webpass = Webpass
window.Sortable = Sortable
window.Chart = Chart

window.FloatingUI = {
    computePosition,
    flip,
    shift,
    offset
}

// Alpine plugins (Livewire v4 compatible)
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Clipboard)
    window.Alpine.plugin(intersect)
    window.Alpine.plugin(collapse)
})

// Cropper init
document.addEventListener('DOMContentLoaded', () => {
    const image = document.getElementById('image')

    if (image) {
        new Cropper(image)
    }
})