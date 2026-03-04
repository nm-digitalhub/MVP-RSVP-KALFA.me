@extends('layouts.app')

@section('title', __('Billing & Entitlements'))

@section('containerWidth', 'max-w-3xl')

@section('header')
    <x-page-header
        :title="__('Billing & Entitlements')"
        :subtitle="__('Account overview for current organization')"
    />
@endsection

@section('content')
    <livewire:billing.account-overview />
@endsection
