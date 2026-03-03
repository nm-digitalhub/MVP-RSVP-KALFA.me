@extends('layouts.app')

@section('title', __('System Organizations'))

@section('containerWidth', 'max-w-7xl')

@section('header')
    <x-page-header
        :title="__('System Organizations')"
        :subtitle="__('Manage all organizations')"
    />
@endsection

@section('content')
    @livewire('system.organizations.index')
@endsection
