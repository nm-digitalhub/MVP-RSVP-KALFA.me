<x-layouts.enterprise-app>
    <x-slot:title>{{ __('Create event') }}</x-slot:title>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <x-page-header
        :title="__('Create event')"
        :subtitle="__('Add a new event to your organization')"
    />

    <div class="mt-4 sm:mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-start">
        <div
            class="overflow-hidden rounded-[1.5rem] border border-stroke bg-card shadow-sm"
            role="region"
            aria-labelledby="create-event-heading"
        >
            <form
                action="{{ route('dashboard.events.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-5 p-4 sm:p-6 lg:p-8"
                novalidate
                x-data="{ submitting: false, fileName: '' }"
                x-on:submit="submitting = true"
                x-bind:aria-busy="submitting.toString()"
            >
                @csrf

                <h2 id="create-event-heading" class="sr-only">
                    {{ __('Create event form') }}
                </h2>

                <section class="rounded-2xl border border-stroke bg-gradient-to-br from-brand/8 via-card to-card p-5 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="max-w-2xl space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand">
                                {{ __('Create event') }}
                            </p>
                            <p class="text-lg font-semibold leading-tight text-content sm:text-xl">
                                {{ __('Name and date for your event. The public link will be created automatically.') }}
                            </p>
                            <p class="text-sm leading-relaxed text-content-muted">
                                {{ __('Optional text shown on the public RSVP page.') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-full border border-brand/20 bg-brand/10 px-3 py-1 text-xs font-medium text-brand">
                                {{ __('Draft') }}
                            </span>
                            <span class="inline-flex items-center rounded-full border border-stroke bg-card px-3 py-1 text-xs font-medium text-content-muted">
                                RSVP
                            </span>
                            <span class="inline-flex items-center rounded-full border border-stroke bg-card px-3 py-1 text-xs font-medium text-content-muted">
                                16:9
                            </span>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-stroke bg-card p-5 sm:p-6" aria-describedby="event-basic-desc">
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-brand/10 text-brand">
                            <x-heroicon-o-calendar-days class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-content">
                                {{ __('Event details') }}
                            </h3>
                            <p id="event-basic-desc" class="mt-1 text-sm leading-relaxed text-content-muted">
                                {{ __('Name and date for your event. The public link will be created automatically.') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div>
                            <x-ts-input
                                id="name"
                                name="name"
                                type="text"
                                label="{{ __('Event name') }}"
                                value="{{ old('name') }}"
                                required
                                autocomplete="off"
                                aria-required="true"
                            />
                        </div>

                        <div>
                            <x-ts-date
                                id="event_date"
                                name="event_date"
                                label="{{ __('Event date') }}"
                                hint="{{ __('Choose the date when the event takes place.') }}"
                                value="{{ old('event_date') }}"
                                format="YYYY-MM-DD"
                            />
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-stroke bg-card p-5 sm:p-6" aria-describedby="venue-desc">
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-brand/10 text-brand">
                            <x-heroicon-o-map-pin class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-content">
                                {{ __('Venue') }}
                            </h3>
                            <p id="venue-desc" class="mt-1 text-sm leading-relaxed text-content-muted">
                                {{ __('Where the event will be held.') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4">
                        <div>
                            <x-ts-input
                                id="venue_name"
                                name="venue_name"
                                type="text"
                                label="{{ __('Venue name') }}"
                                value="{{ old('venue_name') }}"
                                autocomplete="organization"
                            />
                        </div>
                        <div>
                            <x-ts-input
                                id="venue_address"
                                name="venue_address"
                                type="text"
                                label="{{ __('Venue address') }}"
                                hint="{{ __('Used for \"Navigate to event\" and Add to calendar. Leave blank to use venue name.') }}"
                                value="{{ old('venue_address') }}"
                                placeholder="{{ __('Street, city — used for navigation and calendar') }}"
                            />
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-stroke bg-card p-5 sm:p-6" id="event-image-fieldset">
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-brand/10 text-brand">
                            <x-heroicon-o-paint-brush class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-content">{{ __('Event image') }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-content-muted">
                                {{ __('JPEG, PNG, GIF or WebP. Max 5 MB. Cropped to 16:9.') }}
                            </p>
                        </div>
                    </div>

                    <input type="hidden" name="cropped_image" id="cropped_image" value="" />

                    <div class="mt-5 space-y-4">
                        <input
                            type="file"
                            id="image-input"
                            name="image"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                            class="sr-only"
                            aria-describedby="image-hint"
                            x-on:change="fileName = $event.target.files?.[0]?.name ?? ''"
                        />

                        <label
                            for="image-input"
                            class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-stroke bg-content/[0.03] px-4 py-8 text-center transition-colors hover:border-brand/40 hover:bg-brand/5"
                        >
                            <span class="flex size-12 items-center justify-center rounded-full bg-brand/10 text-brand">
                                <x-heroicon-o-paint-brush class="size-5" />
                            </span>
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-content">{{ __('Choose file') }}</p>
                                <p id="image-hint" class="text-sm text-content-muted">
                                    {{ __('JPEG, PNG, GIF or WebP. Max 5 MB. Cropped to 16:9.') }}
                                </p>
                            </div>
                            <p x-show="fileName" x-text="fileName" class="text-sm font-medium text-brand"></p>
                        </label>

                        <x-input-error :messages="$errors->get('image')" class="mt-1" />

                        <div id="cropper-wrap" class="hidden space-y-3 rounded-2xl border border-stroke bg-content/[0.03] p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" id="cropper-zoom-out" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] rounded-xl border border-stroke bg-card px-3 text-sm font-medium text-content shadow-sm transition hover:bg-content/[0.04] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 active:bg-content/[0.08] touch-manipulation" aria-label="{{ __('Zoom out') }}">
                                    <span aria-hidden="true">−</span>
                                </button>
                                <button type="button" id="cropper-zoom-in" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] rounded-xl border border-stroke bg-card px-3 text-sm font-medium text-content shadow-sm transition hover:bg-content/[0.04] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 active:bg-content/[0.08] touch-manipulation" aria-label="{{ __('Zoom in') }}">
                                    <span aria-hidden="true">+</span>
                                </button>
                                <span class="text-sm text-content-muted">{{ __('Drag to move, zoom or pinch. Crop 16:9.') }}</span>
                            </div>
                            <div id="cropper-viewport" class="cropper-viewport min-h-[220px] overflow-hidden rounded-2xl border border-stroke bg-card max-h-[50vh] sm:max-h-[360px] select-none touch-none" style="touch-action: none; -webkit-user-select: none; user-select: none;">
                                <img id="cropper-image" src="" alt="" class="block max-w-full" />
                            </div>
                        </div>
                    </div>
                </section>

                <div class="rounded-2xl border border-stroke bg-card p-5 sm:p-6">
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-brand/10 text-brand">
                            <x-heroicon-o-clipboard class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-content">{{ __('Description') }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-content-muted">
                                {{ __('Optional event description.') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <x-ts-textarea
                            id="description"
                            name="description"
                            rows="4"
                            label="{{ __('Description') }}"
                            hint="{{ __('Optional event description.') }}"
                            placeholder="{{ __('Optional event description.') }}"
                        >{{ old('description') }}</x-ts-textarea>
                    </div>
                </div>

                <section class="rounded-2xl border border-stroke bg-card p-5 sm:p-6" aria-describedby="rsvp-desc">
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-brand/10 text-brand">
                            <x-heroicon-o-chat-bubble-left-right class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-content">
                                {{ __('RSVP & invitation') }}
                            </h3>
                            <p id="rsvp-desc" class="mt-1 text-sm leading-relaxed text-content-muted">
                                {{ __('Optional text shown on the public RSVP page.') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4">
                        <div>
                            <x-ts-textarea
                                id="rsvp_welcome_message"
                                name="rsvp_welcome_message"
                                rows="3"
                                label="{{ __('RSVP welcome message') }}"
                                hint="{{ __('Shown above the response form. Leave blank to use the default.') }}"
                                placeholder="{{ __('We\'d love to see you among our guests.') }}"
                            >{{ old('rsvp_welcome_message') }}</x-ts-textarea>
                        </div>
                        <div>
                            <x-ts-textarea
                                id="program"
                                name="program"
                                rows="4"
                                label="{{ __('Program') }}"
                                placeholder="{{ __('Optional program or schedule.') }}"
                            >{{ old('program') }}</x-ts-textarea>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-stroke bg-card p-5 sm:p-6">
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-brand/10 text-brand">
                            <x-heroicon-o-information-circle class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-content">{{ __('Additional custom fields') }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-content-muted">{{ __('Optional label-value pairs (e.g. Dress code, Parking info).') }}</p>
                        </div>
                    </div>

                    @php
                        $custom = old('custom', []);
                        $custom = array_values($custom);
                        while (count($custom) < 5) {
                            $custom[] = ['label' => '', 'value' => ''];
                        }
                    @endphp

                    <div class="mt-5 space-y-3">
                        @foreach(array_slice($custom, 0, 5) as $i => $row)
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <x-ts-input
                                    name="custom[{{ $i }}][label]"
                                    type="text"
                                    value="{{ is_array($row) ? ($row['label'] ?? '') : '' }}"
                                    placeholder="{{ __('Label') }}"
                                    aria-label="{{ __('Custom field label') }}"
                                />
                                <x-ts-input
                                    name="custom[{{ $i }}][value]"
                                    type="text"
                                    value="{{ is_array($row) ? ($row['value'] ?? '') : '' }}"
                                    placeholder="{{ __('Value') }}"
                                    aria-label="{{ __('Custom field value') }}"
                                />
                            </div>
                        @endforeach
                    </div>
                </section>

                <div
                    class="sticky bottom-4 z-10 rounded-2xl border border-stroke bg-card/95 p-4 shadow-lg backdrop-blur supports-[backdrop-filter]:bg-card/80"
                    role="group"
                    aria-label="{{ __('Form actions') }}"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-content-muted">
                            {{ __('Name and date for your event. The public link will be created automatically.') }}
                        </p>

                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                            <a
                                href="{{ route('dashboard.events.index') }}"
                                class="inline-flex items-center justify-center min-h-[44px] rounded-lg border border-stroke bg-card px-4 py-2.5 text-sm font-medium text-content shadow-sm transition hover:bg-content/[0.04] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
                                x-bind:class="submitting ? 'pointer-events-none opacity-50' : ''"
                            >
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button type="submit" x-bind:disabled="submitting">
                                <span x-show="!submitting">{{ __('Create event') }}</span>
                                <span x-show="submitting" class="inline-flex items-center gap-2">
                                    <svg class="size-4 animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                                    </svg>
                                    {{ __('Working...') }}
                                </span>
                            </x-primary-button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-6">
            <div class="rounded-2xl border border-stroke bg-card p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-content">{{ __('Additional info') }}</h2>
                <ul class="mt-4 space-y-3 text-sm leading-relaxed text-content-muted">
                    <li class="rounded-xl bg-content/[0.03] px-3 py-2">{{ __('Name and date for your event. The public link will be created automatically.') }}</li>
                    <li class="rounded-xl bg-content/[0.03] px-3 py-2">{{ __('Used for "Navigate to event" and Add to calendar. Leave blank to use venue name.') }}</li>
                    <li class="rounded-xl bg-content/[0.03] px-3 py-2">{{ __('Optional text shown on the public RSVP page.') }}</li>
                </ul>
            </div>

            <div class="rounded-2xl border border-stroke bg-card p-5 shadow-sm">
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center rounded-full border border-stroke bg-content/[0.03] px-3 py-1 text-xs font-medium text-content-muted">{{ __('Event image') }}</span>
                    <span class="inline-flex items-center rounded-full border border-stroke bg-content/[0.03] px-3 py-1 text-xs font-medium text-content-muted">{{ __('Add to calendar') }}</span>
                    <span class="inline-flex items-center rounded-full border border-stroke bg-content/[0.03] px-3 py-1 text-xs font-medium text-content-muted">16:9</span>
                </div>
            </div>
        </aside>
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
</div>
</x-layouts.enterprise-app>
