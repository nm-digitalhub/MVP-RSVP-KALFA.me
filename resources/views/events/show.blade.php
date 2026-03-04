@extends('layouts.guest')

@section('title', $event->name)

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg p-8">
        <h1 class="text-2xl font-semibold text-gray-900">{{ $event->name }}</h1>
        @if($event->event_date)
            <p class="mt-2 text-gray-600"><span class="font-medium text-gray-700">{{ __('Date') }}:</span> {{ $event->event_date->locale(app()->getLocale())->translatedFormat('l, j F Y') }}</p>
        @endif
        @if($event->venue_name)
            <p class="mt-1 text-gray-600"><span class="font-medium text-gray-700">{{ __('Venue') }}:</span> {{ $event->venue_name }}</p>
        @endif
        @if($event->getAttribute('description'))
            <div class="mt-4 text-gray-700"><span class="font-medium text-gray-700">{{ __('Description') }}:</span> {{ $event->description }}</div>
        @endif
    </div>
</div>
@endsection
