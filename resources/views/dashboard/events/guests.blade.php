@extends('layouts.app')

@section('title', __('Guests') . ' — ' . $event->name)

@section('containerWidth', 'max-w-4xl')

@section('header')
    <x-page-header
        :title="__('Guests')"
        :subtitle="$event->name"
    />
@endsection

@section('content')
    <livewire:dashboard.event-guests :event="$event" />
@endsection
