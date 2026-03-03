@extends('layouts.app')

@section('title', __('System Dashboard'))

@section('containerWidth', 'max-w-7xl')

@section('header')
    <x-page-header
        :title="__('System Dashboard')"
        :subtitle="__('Global system overview')"
    />
@endsection

@section('content')
    @livewire('system.dashboard')
@endsection
