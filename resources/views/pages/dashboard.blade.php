@extends('layouts.app')

@section('title', __('Dashboard'))

@section('containerWidth', 'max-w-7xl')

@section('header')
    <x-page-header
        :title="__('Dashboard')"
        :subtitle="__('Organization overview and performance')"
    />
@endsection

@section('content')
    <livewire:dashboard />
@endsection
