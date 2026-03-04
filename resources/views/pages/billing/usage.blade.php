@extends('layouts.app')

@section('title', __('Usage'))

@section('containerWidth', 'max-w-4xl')

@section('header')
    <x-page-header
        :title="__('Usage')"
        :subtitle="__('Feature usage (read-only)')"
    />
@endsection

@section('content')
    <livewire:billing.usage-index />
@endsection
