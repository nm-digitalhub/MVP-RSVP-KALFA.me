@extends('layouts.app')

@section('title', __('Create event'))

@section('containerWidth', 'max-w-2xl')

@section('header')
    <x-page-header
        :title="__('Create event')"
        :subtitle="__('Add a new event to your organization')"
    />
@endsection

@section('content')
    <div
        class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
        role="region"
        aria-labelledby="create-event-heading"
    >
        <form
            action="{{ route('dashboard.events.store') }}"
            method="POST"
            enctype="multipart/form-data"
            class="p-6 space-y-6"
            novalidate
        >
            @csrf

            <h2 id="create-event-heading" class="sr-only">
                {{ __('Create event form') }}
            </h2>

            {{-- Basic details --}}
            <fieldset class="space-y-4" aria-describedby="event-basic-desc">
                <legend class="text-base font-semibold text-gray-900">
                    {{ __('Event details') }}
                </legend>
                <p id="event-basic-desc" class="text-sm text-gray-600">
                    {{ __('Name, URL and date for your event.') }}
                </p>

                <div>
                    <x-input-label for="name" :value="__('Event name')" />
                    <x-text-input
                        id="name"
                        name="name"
                        type="text"
                        class="mt-1 block w-full min-h-[44px]"
                        :value="old('name')"
                        required
                        autocomplete="off"
                        aria-required="true"
                    />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="slug" :value="__('URL slug')" />
                    <x-text-input
                        id="slug"
                        name="slug"
                        type="text"
                        class="mt-1 block w-full min-h-[44px]"
                        :value="old('slug')"
                        required
                        autocomplete="off"
                        aria-required="true"
                        aria-describedby="slug-hint"
                    />
                    <p id="slug-hint" class="mt-1 text-sm text-gray-600">
                        {{ __('Used in event and RSVP URLs. Use lowercase letters, numbers, hyphens.') }}
                    </p>
                    <x-input-error :messages="$errors->get('slug')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="event_date" :value="__('Event date')" />
                    <x-text-input
                        id="event_date"
                        name="event_date"
                        type="date"
                        class="mt-1 block w-full min-h-[44px]"
                        :value="old('event_date')"
                        aria-describedby="event_date-hint"
                    />
                    <p id="event_date-hint" class="sr-only">
                        {{ __('Choose the date when the event takes place.') }}
                    </p>
                    <x-input-error :messages="$errors->get('event_date')" class="mt-1" />
                </div>
            </fieldset>

            {{-- Venue --}}
            <fieldset class="space-y-4" aria-describedby="venue-desc">
                <legend class="text-base font-semibold text-gray-900">
                    {{ __('Venue') }}
                </legend>
                <p id="venue-desc" class="text-sm text-gray-600">
                    {{ __('Where the event will be held.') }}
                </p>

                <div>
                    <x-input-label for="venue_name" :value="__('Venue name')" />
                    <x-text-input
                        id="venue_name"
                        name="venue_name"
                        type="text"
                        class="mt-1 block w-full min-h-[44px]"
                        :value="old('venue_name')"
                        autocomplete="organization"
                    />
                    <x-input-error :messages="$errors->get('venue_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="venue_address" :value="__('Venue address')" />
                    <x-text-input
                        id="venue_address"
                        name="venue_address"
                        type="text"
                        class="mt-1 block w-full min-h-[44px]"
                        :value="old('venue_address')"
                        placeholder="{{ __('Street, city — used for navigation and calendar') }}"
                        aria-describedby="venue_address-hint"
                    />
                    <p id="venue_address-hint" class="mt-1 text-sm text-gray-500">{{ __('Used for "Navigate to event" and Add to calendar. Leave blank to use venue name.') }}</p>
                    <x-input-error :messages="$errors->get('venue_address')" class="mt-1" />
                </div>
            </fieldset>

            {{-- Event image (Cropper.js 16:9) --}}
            <fieldset class="space-y-3" id="event-image-fieldset">
                <legend class="text-sm font-medium text-gray-700">{{ __('Event image') }}</legend>
                <input type="hidden" name="cropped_image" id="cropped_image" value="" />
                <div>
                    <input type="file" id="image-input" name="image" accept="image/jpeg,image/png,image/gif,image/webp" class="mt-1 block w-full min-h-[44px] text-sm text-gray-600 file:mr-4 file:min-h-[44px] file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 file:cursor-pointer" aria-describedby="image-hint" />
                    <p id="image-hint" class="mt-1 text-sm text-gray-500">{{ __('JPEG, PNG, GIF or WebP. Max 5 MB. Cropped to 16:9.') }}</p>
                    <x-input-error :messages="$errors->get('image')" class="mt-1" />
                </div>
                <div id="cropper-wrap" class="hidden mt-3 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="cropper-zoom-out" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] px-3 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 active:bg-gray-100 touch-manipulation" aria-label="{{ __('Zoom out') }}">
                            <span aria-hidden="true">−</span>
                        </button>
                        <button type="button" id="cropper-zoom-in" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] px-3 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 active:bg-gray-100 touch-manipulation" aria-label="{{ __('Zoom in') }}">
                            <span aria-hidden="true">+</span>
                        </button>
                        <span class="text-sm text-gray-500">{{ __('Drag to move, zoom or pinch. Crop 16:9.') }}</span>
                    </div>
                    <div id="cropper-viewport" class="cropper-viewport max-h-[50vh] sm:max-h-[360px] min-h-[220px] overflow-hidden rounded-lg border border-gray-200 bg-gray-100 select-none touch-none" style="touch-action: none; -webkit-user-select: none; user-select: none;">
                        <img id="cropper-image" src="" alt="" class="max-w-full block" />
                    </div>
                </div>
            </fieldset>

            {{-- Description --}}
            <div>
                <x-input-label for="description" :value="__('Description')" />
                <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200 rtl:text-end" placeholder="{{ __('Optional event description.') }}">{{ old('description') }}</textarea>
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
                    <textarea id="rsvp_welcome_message" name="rsvp_welcome_message" rows="3" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200 rtl:text-end" placeholder="{{ __("We'd love to see you among our guests.") }}">{{ old('rsvp_welcome_message') }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Shown above the response form. Leave blank to use the default.') }}</p>
                    <x-input-error :messages="$errors->get('rsvp_welcome_message')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="program" :value="__('Program')" />
                    <textarea id="program" name="program" rows="4" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200 rtl:text-end" placeholder="{{ __('Optional program or schedule.') }}">{{ old('program') }}</textarea>
                    <x-input-error :messages="$errors->get('program')" class="mt-1" />
                </div>
            </fieldset>

            {{-- Custom fields (additional info) --}}
            <fieldset class="space-y-3">
                <legend class="text-sm font-medium text-gray-700">{{ __('Additional custom fields') }}</legend>
                <p class="text-sm text-gray-500">{{ __('Optional label-value pairs (e.g. Dress code, Parking info).') }}</p>
                @php
                    $custom = old('custom', []);
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

            {{-- Actions --}}
            <div
                class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end"
                role="group"
                aria-label="{{ __('Form actions') }}"
            >
                <a
                    href="{{ route('dashboard.events.index') }}"
                    class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-colors duration-200"
                >
                    {{ __('Cancel') }}
                </a>
                <x-primary-button type="submit">
                    {{ __('Create event') }}
                </x-primary-button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function runWhenCropperReady() {
                if (typeof window.Cropper !== 'function') {
                    setTimeout(runWhenCropperReady, 50);
                    return;
                }
                const form = document.querySelector('form');
                const fileInput = document.getElementById('image-input');
                const cropperWrap = document.getElementById('cropper-wrap');
                const cropperImg = document.getElementById('cropper-image');
                const croppedInput = document.getElementById('cropped_image');
                const viewport = document.getElementById('cropper-viewport');
                const EVENT_ASPECT = 16 / 9;
                const ZOOM_STEP = 0.1;
                const PINCH_SCALE_CLAMP = 0.15;
                let cropper = null;
                let submittingAfterCrop = false;
                let lastPinchDistance = 0;

                function cropperZoom(inOut) {
                    if (!cropper) return;
                    const img = cropper.getCropperImage();
                    if (img) img.$zoom(inOut === 'in' ? ZOOM_STEP : -ZOOM_STEP);
                }

                document.getElementById('cropper-zoom-in')?.addEventListener('click', function () { cropperZoom('in'); });
                document.getElementById('cropper-zoom-out')?.addEventListener('click', function () { cropperZoom('out'); });

                function getPinchDistance(touches) {
                    if (touches.length < 2) return 0;
                    return Math.hypot(touches[1].clientX - touches[0].clientX, touches[1].clientY - touches[0].clientY);
                }

                viewport?.addEventListener('touchstart', function (e) {
                    if (e.touches.length === 2) lastPinchDistance = getPinchDistance(e.touches);
                }, { passive: true });

                viewport?.addEventListener('touchmove', function (e) {
                    if (e.touches.length !== 2 || !cropper) return;
                    e.preventDefault();
                    const d = getPinchDistance(e.touches);
                    if (lastPinchDistance > 0) {
                        let scale = (d - lastPinchDistance) / lastPinchDistance;
                        scale = Math.max(-PINCH_SCALE_CLAMP, Math.min(PINCH_SCALE_CLAMP, scale));
                        const img = cropper.getCropperImage();
                        if (img) img.$zoom(scale);
                    }
                    lastPinchDistance = d;
                }, { passive: false });

                viewport?.addEventListener('touchend', function (e) {
                    if (e.touches.length < 2) lastPinchDistance = 0;
                }, { passive: true });

                viewport?.addEventListener('touchcancel', function (e) {
                    if (e.touches.length < 2) lastPinchDistance = 0;
                }, { passive: true });

                fileInput.addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (!file) return;
                    if (cropper) { cropper.destroy(); cropper = null; }
                    const url = URL.createObjectURL(file);
                    cropperImg.src = url;
                    cropperWrap.classList.remove('hidden');
                    cropperImg.onload = function () {
                        if (cropper) cropper.destroy();
                        cropper = new window.Cropper(cropperImg, { container: viewport });
                        const sel = cropper.getCropperSelection();
                        if (sel) sel.aspectRatio = EVENT_ASPECT;
                    };
                });

                form.addEventListener('submit', async function (e) {
                    if (submittingAfterCrop) return;
                    if (!cropper) return;
                    const canvas = cropper.getCropperCanvas();
                    if (!canvas) return;
                    e.preventDefault();
                    try {
                        const out = await canvas.$toCanvas({ width: 1600, height: 900 });
                        croppedInput.value = out.toDataURL('image/jpeg', 0.9);
                        fileInput.removeAttribute('name');
                        submittingAfterCrop = true;
                        form.submit();
                    } catch (err) {
                        console.error(err);
                        submittingAfterCrop = true;
                        form.submit();
                    }
                });
            }
            runWhenCropperReady();
        });
    </script>
    @endpush
@endsection
