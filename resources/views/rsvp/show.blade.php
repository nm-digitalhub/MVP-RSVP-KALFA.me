<x-layouts.guest>
    <x-slot:title>{{ __('RSVP') }} — {{ $invitation->event->name }}</x-slot:title>

<div class="min-h-screen bg-gray-50 py-8 px-4 sm:py-12 sm:px-6 flex items-start sm:items-center justify-center">
    <article class="w-full max-w-2xl" aria-labelledby="rsvp-heading">
        @if(session('success'))
            <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200" role="status" aria-live="polite">
                {{ __('Thank you! Your response has been saved.') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($invitation->event->imageUrl)
                <section class="rounded-t-xl overflow-hidden border-b border-gray-200" aria-hidden="true">
                    <img src="{{ $invitation->event->imageUrl }}" alt="" class="w-full h-48 sm:h-64 object-cover" width="672" height="256" />
                </section>
            @endif

            {{-- Event summary (aligned with dashboard events show) --}}
            <section class="p-6" aria-labelledby="rsvp-heading">
                <h1 id="rsvp-heading" class="text-xl font-semibold text-gray-900 sm:text-2xl">
                    {{ $invitation->event->name }}
                </h1>
                @if($invitation->event->event_date)
                    @php
                        $ed = $invitation->event->event_date;
                        $locale = app()->getLocale();
                    @endphp
                    <p class="mt-2 text-sm text-gray-600 leading-relaxed">
                        {{ $ed->translatedFormat('l, F j, Y') }}
                    </p>
                    <p class="mt-0.5 text-sm text-gray-500 leading-relaxed">
                        {{ $ed->format($locale === 'he' ? 'd.m.Y' : 'Y-m-d') }}
                    </p>
                @endif
                @if($invitation->guest)
                    <p class="mt-3 text-sm font-medium text-gray-800 leading-relaxed">
                        {{ __('Hello') }}, {{ $invitation->guest->name }}
                    </p>
                @endif
            </section>

            @if(! empty($invitation->event->settings['description'] ?? null))
                <div class="px-6 pb-6 border-t border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2 pt-6">{{ __('Description') }}</h2>
                    <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap">{{ $invitation->event->settings['description'] }}</p>
                </div>
            @endif

            @if(count($invitation->event->customFields) > 0)
                <div class="px-6 pb-6 border-t border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2 pt-6">{{ __('Additional info') }}</h2>
                    <dl class="space-y-1 text-sm">
                        @foreach($invitation->event->customFields as $row)
                            <div><dt class="inline font-medium text-gray-700">{{ e($row['label']) }}:</dt> <dd class="inline text-gray-600">{{ e($row['value']) }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- Actions: Add to calendar, Navigate, Share --}}
            @php
                $addToCalendarUrl = $eventLinks->addToCalendarUrl($invitation->event);
            @endphp
            <div class="flex flex-wrap gap-2 px-6 py-4 border-t border-gray-100">
                @if($addToCalendarUrl)
                    <a href="{{ $addToCalendarUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 cursor-pointer">
                        <x-heroicon-o-calendar-days class="h-5 w-5 text-gray-500" />
                        <span>{{ __('Add to calendar') }}</span>
                    </a>
                @endif
                @foreach($eventLinks->navigationLinks($invitation->event) as $nav)
                    <a href="{{ $nav['url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 cursor-pointer">
                        <x-heroicon-o-map-pin class="h-5 w-5 text-gray-500" />
                        <span>{{ __($nav['label_key']) }}</span>
                    </a>
                @endforeach
                <div class="inline-flex" data-rsvp-share data-share-url="{{ e(url()->current()) }}" data-share-title="{{ e($invitation->event->name) }}" data-share-label="{{ e(__('Share event')) }}" data-share-copied="{{ e(__('Link copied.')) }}">
                    <button type="button" class="rsvp-share-trigger inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 cursor-pointer">
                        <x-heroicon-o-share-across class="h-5 w-5 text-gray-500" />
                        <span class="rsvp-share-label">{{ __('Share event') }}</span>
                    </button>
                </div>
            </div>
            <script>
                document.querySelectorAll('[data-rsvp-share]').forEach(function (el) {
                    var btn = el.querySelector('.rsvp-share-trigger');
                    var labelEl = el.querySelector('.rsvp-share-label');
                    if (!btn || !labelEl) return;
                    var url = el.getAttribute('data-share-url');
                    var title = el.getAttribute('data-share-title');
                    var label = el.getAttribute('data-share-label');
                    var copiedText = el.getAttribute('data-share-copied');
                    btn.addEventListener('click', function () {
                        if (navigator.share) {
                            navigator.share({ title: title, url: url }).catch(function () { doCopy(); });
                        } else {
                            doCopy();
                        }
                    });
                    function doCopy() {
                        navigator.clipboard.writeText(url).then(function () {
                            labelEl.textContent = copiedText;
                            setTimeout(function () { labelEl.textContent = label; }, 2000);
                        });
                    }
                });
            </script>

            {{-- RSVP form --}}
            <div class="px-6 py-6 border-t border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700 mb-2">{{ __('Response') }}</h2>
                @php
                    $welcomeMessage = $invitation->event->settings['rsvp_welcome_message'] ?? null;
                    $welcomeMessage = $welcomeMessage !== null && $welcomeMessage !== '' ? $welcomeMessage : __(config('events.rsvp.default_welcome_message_key', "We'd love to see you among our guests."));
                @endphp
                <p class="mb-4 text-sm text-gray-600 leading-relaxed">
                    {{ $welcomeMessage }}
                </p>
                <p class="mb-4 text-sm text-gray-500 leading-relaxed">
                    {{ __('Submit your RSVP via the API or use the form below.') }}
                </p>

                <form id="rsvp-form"
                    action="{{ route('rsvp.responses.store', $invitation->slug) }}"
                    method="POST"
                    class="space-y-6"
                    data-rsvp-event-name="{{ e($invitation->event->name) }}"
                    data-rsvp-invitation-slug="{{ e($invitation->slug) }}"
                    data-rsvp-event-id="{{ (string) $invitation->event->id }}">
                    @csrf
                    @if($errors->isNotEmpty())
                        <div class="rounded-lg bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200" role="alert">
                            {{ __('Please select your response and try again.') }}
                        </div>
                    @endif

                    <style>
                        .rsvp-option-label.rsvp-option-selected { border-color: rgb(79 70 229); background-color: rgb(224 231 255); color: rgb(55 48 163); box-shadow: inset 0 0 0 2px rgb(99 102 241 / 0.4); }
                        .rsvp-option-label.rsvp-option-selected .rsvp-option-dot { opacity: 1; }
                    </style>
                    <fieldset class="space-y-3" aria-label="{{ __('RSVP Response Options') }}">
                        <legend class="sr-only">{{ __('Choose attending, not attending, or maybe.') }}</legend>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3" role="group">
                            @php
                                $options = [
                                    'yes'   => __('Yes'),
                                    'no'    => __('No'),
                                    'maybe' => __('Maybe'),
                                ];
                            @endphp
                            @foreach($options as $value => $label)
                                <label class="rsvp-option-label relative flex min-h-[44px] cursor-pointer items-center justify-center rounded-lg border-2 px-4 py-3 text-sm font-medium transition-all duration-200 focus-within:ring-2 focus-within:ring-brand focus-within:ring-offset-2 border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:bg-gray-50"
                                    data-rsvp-label-for="{{ $value }}">
                                    <input type="radio"
                                        name="response"
                                        value="{{ $value }}"
                                        id="response-{{ $value }}"
                                        class="absolute inset-0 h-full w-full cursor-pointer appearance-none rounded-lg rsvp-response-option"
                                        data-rsvp-option="{{ $value }}"
                                        required
                                        aria-describedby="response-{{ $value }}-label"
                                        @if($value === 'yes') checked @endif>
                                    <span id="response-{{ $value }}-label" class="relative z-10 pointer-events-none">{{ $label }}</span>
                                    <span class="rsvp-option-dot absolute top-2 end-2 h-2 w-2 rounded-full bg-brand opacity-0 transition-opacity duration-200 pointer-events-none rtl:end-auto rtl:start-2" aria-hidden="true"></span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="pt-2">
                        <x-primary-button type="submit" id="rsvp-submit-btn" class="min-h-[44px] w-full cursor-pointer px-6 text-base font-semibold sm:w-auto sm:min-w-[200px]" data-rsvp-submit-trigger>
                            {{ __('Submit RSVP') }}
                        </x-primary-button>
                    </div>
                </form>
                <script>
                    (function () {
                        var form = document.getElementById('rsvp-form');
                        if (!form) return;

                        function updateRsvpSelectedState() {
                            var checked = form.querySelector('input[name="response"]:checked');
                            form.querySelectorAll('.rsvp-option-label').forEach(function (label) {
                                var input = label.querySelector('input[name="response"]');
                                if (input && input === checked) {
                                    label.classList.add('rsvp-option-selected');
                                } else {
                                    label.classList.remove('rsvp-option-selected');
                                }
                            });
                        }

                        form.querySelectorAll('input[name="response"]').forEach(function (radio) {
                            radio.addEventListener('change', updateRsvpSelectedState);
                        });
                        updateRsvpSelectedState();

                        function getSelectedResponse() {
                            var radio = form.querySelector('input[name="response"]:checked');
                            return radio ? radio.value : null;
                        }

                        function dispatchRsvpSubmit(response) {
                            var detail = {
                                response: response,
                                eventName: form.getAttribute('data-rsvp-event-name') || '',
                                eventId: form.getAttribute('data-rsvp-event-id') || '',
                                invitationSlug: form.getAttribute('data-rsvp-invitation-slug') || ''
                            };
                            try {
                                window.dispatchEvent(new CustomEvent('RSVP_SUBMIT', { detail: detail }));
                                if (typeof window.dataLayer !== 'undefined') {
                                    window.dataLayer = window.dataLayer || [];
                                    window.dataLayer.push({
                                        event: 'RSVP_SUBMIT',
                                        rsvp_response: detail.response,
                                        rsvp_event_name: detail.eventName,
                                        rsvp_event_id: detail.eventId,
                                        rsvp_invitation_slug: detail.invitationSlug
                                    });
                                }
                            } catch (e) {}
                        }

                        form.addEventListener('submit', function (ev) {
                            var response = getSelectedResponse();
                            if (response) {
                                dispatchRsvpSubmit(response);
                            }
                        });
                    })();
                </script>
            </div>
        </div>
    </article>
</div>
</x-layouts.guest>
