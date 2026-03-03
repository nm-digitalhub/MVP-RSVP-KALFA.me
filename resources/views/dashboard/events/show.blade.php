@extends('layouts.app')

@section('title', $event->name)

@section('content')
<div class="min-h-screen bg-[#F9FAFB] py-12 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h1 class="text-2xl font-semibold text-gray-900">{{ $event->name }}</h1>
                @if($event->event_date)
                    <p class="mt-2 text-gray-600">{{ $event->event_date->format('Y-m-d') }}</p>
                @endif
                @if($event->venue_name)
                    <p class="mt-1 text-gray-600">{{ $event->venue_name }}</p>
                @endif
                <p class="mt-2">
                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                        @switch($event->status->value ?? '')
                            @case('draft') bg-gray-100 text-gray-800 @break
                            @case('pending_payment') bg-amber-100 text-amber-800 @break
                            @case('active') bg-green-100 text-green-800 @break
                            @default bg-gray-100 text-gray-800
                        @endswitch">
                        {{ $event->status->value ?? __('—') }}
                    </span>
                </p>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-500">{{ __('Guests') }}: {{ $event->guests->count() }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ __('Tables') }}: {{ $event->eventTables->count() }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
