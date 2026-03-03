@extends('layouts.guest')

@section('title', __('RSVP') . ' — ' . $invitation->event->name)

@section('content')
<div class="min-h-screen bg-[#F9FAFB] py-12 px-4">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg p-8">
        <h1 class="text-2xl font-semibold text-gray-900">{{ $invitation->event->name }}</h1>
        @if($invitation->event->event_date)
            <p class="mt-2 text-gray-600">{{ $invitation->event->event_date->format('l, F j, Y') }}</p>
        @endif
        @if($invitation->guest)
            <p class="mt-2 text-gray-700">{{ __('Hello') }}, {{ $invitation->guest->name }}</p>
        @endif
        <p class="mt-4 text-gray-600">{{ __('Submit your RSVP via the API or use the form below.') }}</p>
        <form action="{{ route('rsvp.responses.store', $invitation->slug) }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div>
                <label for="response" class="block text-sm font-medium text-gray-700">{{ __('Response') }}</label>
                <select id="response" name="response" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2">
                    <option value="yes">{{ __('Yes') }}</option>
                    <option value="no">{{ __('No') }}</option>
                    <option value="maybe">{{ __('Maybe') }}</option>
                </select>
            </div>
            <button type="submit" class="rounded-full bg-gray-900 px-6 py-3 text-white font-medium hover:bg-gray-800 transition">
                {{ __('Submit RSVP') }}
            </button>
        </form>
    </div>
</div>
@endsection
