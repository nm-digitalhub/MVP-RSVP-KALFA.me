@extends('layouts.app')

@section('title', __('Organizations'))

@section('containerWidth', 'max-w-3xl')

@section('header')
    <x-page-header
        :title="__('Organizations')"
        :subtitle="__('Manage and switch your organizations')"
    />
@endsection

@section('content')
    <livewire:organizations.index />
@endsection
