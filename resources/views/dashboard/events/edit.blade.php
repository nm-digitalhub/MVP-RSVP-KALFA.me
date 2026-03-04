@extends('layouts.app')

@section('title', __('Edit event'))

@section('containerWidth', 'max-w-2xl')

@section('header')
    <x-page-header
        :title="__('Edit event')"
        :subtitle="$event->name"
    />
@endsection

@section('content')
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form action="{{ route('dashboard.events.update', $event) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="name" :value="__('Event name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full min-h-[44px]" :value="old('name', $event->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="slug" :value="__('URL slug')" />
                <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full min-h-[44px]" :value="old('slug', $event->slug)" />
                <x-input-error :messages="$errors->get('slug')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="event_date" :value="__('Event date')" />
                <x-text-input id="event_date" name="event_date" type="date" class="mt-1 block w-full min-h-[44px]" :value="old('event_date', $event->event_date?->format('Y-m-d'))" />
                <x-input-error :messages="$errors->get('event_date')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="venue_name" :value="__('Venue name')" />
                <x-text-input id="venue_name" name="venue_name" type="text" class="mt-1 block w-full min-h-[44px]" :value="old('venue_name', $event->venue_name)" />
                <x-input-error :messages="$errors->get('venue_name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="venue_address" :value="__('Venue address')" />
                <x-text-input id="venue_address" name="venue_address" type="text" class="mt-1 block w-full min-h-[44px]" :value="old('venue_address', $event->settings['venue_address'] ?? '')" placeholder="{{ __('Street, city — used for navigation and calendar') }}" />
                <p class="mt-1 text-sm text-gray-500">{{ __('Used for "Navigate to event" and Add to calendar. Leave blank to use venue name.') }}</p>
                <x-input-error :messages="$errors->get('venue_address')" class="mt-1" />
            </div>

            {{-- Event image (MediaLibrary + Cropper.js 16:9) --}}
            <fieldset class="space-y-3" id="event-image-fieldset">
                <legend class="text-sm font-medium text-gray-700">{{ __('Event image') }}</legend>
                <input type="hidden" name="cropped_image" id="cropped_image" value="" />
                @if($event->getFirstMediaUrl('event-image'))
                    <div class="flex flex-wrap items-center gap-4">
                        <img src="{{ $event->getFirstMediaUrl('event-image', 'thumb') }}" alt="" class="h-24 w-auto rounded-lg border border-gray-200 object-cover" width="400" height="225" />
                        <label class="inline-flex min-h-[44px] cursor-pointer items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            {{ __('Remove image') }}
                        </label>
                    </div>
                @endif
                <div>
                    <input type="file" id="image-input" name="image" accept="image/jpeg,image/png,image/gif,image/webp" class="mt-1 block w-full min-h-[44px] text-sm text-gray-600 file:mr-4 file:min-h-[44px] file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 file:cursor-pointer" aria-describedby="image-hint" />
                    <p id="image-hint" class="mt-1 text-sm text-gray-500">{{ __('JPEG, PNG, GIF or WebP. Max 5 MB. Cropped to 16:9.') }}</p>
                    <x-input-error :messages="$errors->get('image')" class="mt-1" />
                </div>
                <div id="cropper-wrap" class="hidden mt-3 space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="cropper-zoom-out" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] px-3 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                            −
                        </button>
                        <button type="button" id="cropper-zoom-in" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] px-3 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                            +
                        </button>
                        <span class="text-sm text-gray-500">
                            {{ __('Drag to move, zoom or pinch. Crop 16:9.') }}
                        </span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <!-- CROP AREA -->
                        <div id="cropper-viewport"
                             class="max-h-[50vh] sm:max-h-[360px] min-h-[220px] overflow-hidden rounded-lg border border-gray-200 bg-gray-100 select-none"
                             style="-webkit-user-select:none; user-select:none; touch-action:manipulation;">
                            <img id="cropper-image"
                                 src=""
                                 alt=""
                                 class="max-w-full block pointer-events-none"/>
                        </div>

                        <!-- LIVE PREVIEW -->
                        <div class="flex flex-col gap-2">
                            <span class="text-sm text-gray-500">
                                {{ __('Preview') }}
                            </span>
                            <div id="crop-preview"
                                 class="w-full aspect-video overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>

            {{-- Description --}}
            <div>
                <x-input-label for="description" :value="__('Description')" />
                <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200 rtl:text-end" placeholder="{{ __('Optional event description.') }}">{{ old('description', $event->settings['description'] ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </div>

            {{-- RSVP & invitation --}}
            <fieldset class="space-y-4" aria-describedby="rsvp-desc">
                <legend class="text-base font-semibold text-gray-900">
                    {{ __('RSVP & invitation') }}
                </legend>
                <p id="rsvp-desc" class="text-sm text-gray-600">
                    {{ __('Optional text shown on the public RSVP page.') }}
                </p>
                <div>
                    <x-input-label for="rsvp_welcome_message" :value="__('RSVP welcome message')" />
                    <textarea id="rsvp_welcome_message" name="rsvp_welcome_message" rows="3" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200 rtl:text-end" placeholder="{{ __("We'd love to see you among our guests.") }}">{{ old('rsvp_welcome_message', $event->settings['rsvp_welcome_message'] ?? '') }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Shown above the response form. Leave blank to use the default.') }}</p>
                    <x-input-error :messages="$errors->get('rsvp_welcome_message')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="program" :value="__('Program')" />
                    <textarea id="program" name="program" rows="4" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200 rtl:text-end" placeholder="{{ __('Optional program or schedule.') }}">{{ old('program', $event->settings['program'] ?? '') }}</textarea>
                    <x-input-error :messages="$errors->get('program')" class="mt-1" />
                </div>
            </fieldset>

            {{-- Custom fields (additional info) --}}
            <fieldset class="space-y-3">
                <legend class="text-sm font-medium text-gray-700">{{ __('Additional custom fields') }}</legend>
                <p class="text-sm text-gray-500">{{ __('Optional label-value pairs (e.g. Dress code, Parking info).') }}</p>
                @php
                    $custom = old('custom', $event->customFields());
                    $custom = array_values($custom);
                    while (count($custom) < 5) {
                        $custom[] = ['label' => '', 'value' => ''];
                    }
                @endphp
                @foreach(array_slice($custom, 0, 5) as $i => $row)
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <x-text-input name="custom[{{ $i }}][label]" type="text" class="min-h-[44px]" :value="is_array($row) ? ($row['label'] ?? '') : ''" placeholder="{{ __('Label') }}" />
                        <x-text-input name="custom[{{ $i }}][value]" type="text" class="min-h-[44px]" :value="is_array($row) ? ($row['value'] ?? '') : ''" placeholder="{{ __('Value') }}" />
                    </div>
                @endforeach
            </fieldset>

            <div class="flex flex-wrap gap-3 pt-2">
                <x-primary-button type="submit">{{ __('Update event') }}</x-primary-button>
                <a href="{{ route('dashboard.events.show', $event) }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-colors duration-200 cursor-pointer">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
<script>

document.addEventListener('DOMContentLoaded', () => {

    const waitForCropper = () => {

        if (typeof window.Cropper !== 'function') {
            setTimeout(waitForCropper, 50)
            return
        }

        const form = document.querySelector('form')
        const fileInput = document.getElementById('image-input')
        const cropperWrap = document.getElementById('cropper-wrap')
        const cropperImg = document.getElementById('cropper-image')
        const croppedInput = document.getElementById('cropped_image')

        const zoomInBtn = document.getElementById('cropper-zoom-in')
        const zoomOutBtn = document.getElementById('cropper-zoom-out')

        const EVENT_ASPECT = 16 / 9
        const ZOOM_STEP = 0.1

        let cropper = null
        let submittingAfterCrop = false


        /* ---------------------------------------------
        IMAGE SELECT
        --------------------------------------------- */

        fileInput.addEventListener('change', e => {

            const file = e.target.files?.[0]
            if (!file) return

            if (cropper) {
                cropper.destroy()
                cropper = null
            }

            const url = URL.createObjectURL(file)

            cropperImg.src = url
            cropperWrap.classList.remove('hidden')

            cropperImg.onload = () => {

                requestAnimationFrame(() => {

                    if (cropper) cropper.destroy()

                    cropper = new Cropper(cropperImg, {

                        aspectRatio: EVENT_ASPECT,
                        viewMode: 1,
                        dragMode: "move",
                        autoCropArea: 1,

                        zoomable: true,
                        movable: true,
                        responsive: true,
                        background: false,

                        checkOrientation: true,
                        wheelZoomRatio: 0.1,

                        crop() {
                            const canvas = cropper.getCroppedCanvas({
                                width: 320,
                                height: 180
                            })
                            if (!canvas) return
                            const preview = document.getElementById('crop-preview')
                            preview.innerHTML = ''
                            preview.appendChild(canvas)
                        }

                    })

                })

            }

        })


        /* ---------------------------------------------
        ZOOM BUTTONS
        --------------------------------------------- */

        zoomInBtn?.addEventListener('click', () => {
            if (!cropper) return
            cropper.zoom(ZOOM_STEP)
        })

        zoomOutBtn?.addEventListener('click', () => {
            if (!cropper) return
            cropper.zoom(-ZOOM_STEP)
        })


        /* ---------------------------------------------
        FORM SUBMIT
        --------------------------------------------- */

        form.addEventListener('submit', async e => {

            if (submittingAfterCrop) return

            if (!cropper) return

            const canvas = cropper.getCroppedCanvas({
                width: 1600,
                height: 900
            })

            if (!canvas) return

            e.preventDefault()

            try {

                const dataUrl = canvas.toDataURL('image/jpeg', 0.9)

                croppedInput.value = dataUrl

                /* prevent original file upload */
                fileInput.removeAttribute('name')

                submittingAfterCrop = true

                form.submit()

            } catch (err) {

                console.error(err)

                submittingAfterCrop = true
                form.submit()

            }

        })

    }

    waitForCropper()

})

</script>
@endpush
@endsection