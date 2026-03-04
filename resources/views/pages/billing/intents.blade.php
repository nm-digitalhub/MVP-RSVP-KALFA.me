@extends('layouts.app')

@section('title', __('Billing intents'))

@section('containerWidth', 'max-w-4xl')

@section('header')
    <x-page-header
        :title="__('Billing intents')"
        :subtitle="__('Purchase intents (read-only)')"
    />
@endsection

@section('content')
    <livewire:billing.billing-intents-index />
@endsection
