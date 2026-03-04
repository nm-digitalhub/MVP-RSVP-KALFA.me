@extends('layouts.app')

@section('title', __('Create organization'))

@section('containerWidth', 'max-w-lg')

@section('header')
    <x-page-header
        :title="__('Create organization')"
        :subtitle="__('Add a new organization to get started.')"
    />
@endsection

@section('content')
    <livewire:organizations.create />
@endsection
