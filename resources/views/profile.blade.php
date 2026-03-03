@extends('layouts.app')

@section('title', __('Profile'))

@section('containerWidth', 'max-w-3xl')

@section('header')
    <x-page-header
        :title="__('Profile')"
        :subtitle="__('Manage your account settings')"
    />
@endsection

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @livewire('profile.update-profile-information-form')
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @livewire('profile.update-password-form')
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @livewire('profile.delete-user-form')
        </div>
    </div>
@endsection
