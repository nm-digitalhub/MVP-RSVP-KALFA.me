@extends('layouts.app')

@section('title', __('Seat assignments') . ' — ' . $event->name)

@section('containerWidth', 'max-w-4xl')

@section('header')
    <x-page-header
        :title="__('Seat assignments')"
        :subtitle="$event->name"
    />
@endsection

@section('content')
    <livewire:dashboard.event-seat-assignments :event="$event" />
@endsection
