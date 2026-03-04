/**
 * Event SaaS App Entry — Alpine.js + Tailwind
 */

import './bootstrap'
import 'flowbite'

import $ from 'jquery'
window.$ = window.jQuery = $

import Cropper from 'cropperjs'

import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm'
import Clipboard from '@ryangjchandler/alpine-clipboard'

Alpine.plugin(Clipboard)

document.addEventListener('DOMContentLoaded', () => {
    const image = document.getElementById('image')

    if (image) {
        new Cropper(image)
    }
})

Livewire.start()