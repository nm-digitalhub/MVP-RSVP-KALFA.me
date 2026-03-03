@extends('layouts.guest')

@section('title', $event->name)

@section('content')
<div class="min-h-screen bg-[#F9FAFB] py-12 px-4">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg p-8">
        <h1 class="text-2xl font-semibold text-gray-900">{{ $event->name }}</h1>
        @if($event->event_date)
            <p class="mt-2 text-gray-600">{{ $event->event_date->format('l, F j, Y') }}</p>
        @endif
        @if($event->venue_name)
            <p class="mt-1 text-gray-600">{{ $event->venue_name }}</p>
        @endif
        @if($event->getAttribute('description'))
            <div class="mt-4 text-gray-700">{{ $event->description }}</div>
        @endif
    </div>
</div>
@endsection
