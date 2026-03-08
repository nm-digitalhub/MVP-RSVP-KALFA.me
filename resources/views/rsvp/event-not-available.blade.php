<x-layouts.guest>
    <x-slot:title>{{ __('Event not available') }}</x-slot:title>

<div class="min-h-screen bg-gray-50 py-12 px-4 flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 text-center">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('Event not available') }}</h1>
        <p class="mt-3 text-gray-600">{{ __('This RSVP link is not active yet. The event may still be in preparation or payment has not been completed.') }}</p>
        <p class="mt-2 text-sm text-gray-500">{{ __('Please try again later or contact the organizer.') }}</p>
    </div>
</div>
</x-layouts.guest>
