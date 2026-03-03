@extends('layouts.app')

@section('title', __('System Users'))

@section('containerWidth', 'max-w-7xl')

@section('header')
    <x-page-header
        :title="__('System Users')"
        :subtitle="__('Manage all users')"
    />
@endsection

@section('content')
    @livewire('system.users.index')
@endsection
