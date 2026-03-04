@extends('layouts.app')

@section('title', __('Tables') . ' — ' . $event->name)

@section('containerWidth', 'max-w-3xl')

@section('header')
    <x-page-header
        :title="__('Tables')"
        :subtitle="$event->name"
    />
    @if($event->eventTables->isNotEmpty())
        <p class="mt-1 text-sm text-gray-500">
            <a href="{{ route('dashboard.events.seat-assignments.index', $event) }}" class="font-medium text-indigo-600 hover:text-indigo-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 rounded">
                {{ __('Assign guests to tables') }}
            </a>
        </p>
    @endif
@endsection

@section('content')
    <livewire:dashboard.event-tables :event="$event" />
@endsection
