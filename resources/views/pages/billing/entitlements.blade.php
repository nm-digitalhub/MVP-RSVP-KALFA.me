@extends('layouts.app')

@section('title', __('Entitlements'))

@section('containerWidth', 'max-w-4xl')

@section('header')
    <x-page-header
        :title="__('Entitlements')"
        :subtitle="__('Feature grants for this account')"
    />
@endsection

@section('content')
    <livewire:billing.entitlements-index />
@endsection
