@extends('layouts.app')

@section('title', __('Invitations') . ' — ' . $event->name)

@section('containerWidth', 'max-w-4xl')

@section('header')
    <x-page-header
        :title="__('Invitations')"
        :subtitle="$event->name"
    />
@endsection

@section('content')
    <livewire:dashboard.event-invitations :event="$event" />
@endsection
