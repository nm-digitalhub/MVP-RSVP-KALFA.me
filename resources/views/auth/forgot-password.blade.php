@extends('layouts.guest')

@section('title', 'איפוס סיסמה')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-auto flex justify-center">
                <a href="{{ route('home') }}" class="text-2xl font-bold text-indigo-600">
                    NM-DigitalHUB
                </a>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                איפוס סיסמה
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                שכחת את הסיסמה? אין בעיה! הזן את כתובת האימייל שלך ונשלח לך קישור לאיפוס.
            </p>
        </div>
        
        <form class="mt-8 space-y-6" action="{{ route('password.email') }}" method="POST">
            @csrf
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">כתובת אימייל</label>
                <input id="email" name="email" type="email" autocomplete="email" required 
                       value="{{ old('email') }}"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                       placeholder="האימייל שלך">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if(session('status'))
                <div class="rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="me-3">
                            <p class="text-sm text-green-800">
                                {{ session('status') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span class="absolute right-0 inset-y-0 flex items-center ps-3">
                        <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                    </span>
                    שלח קישור לאיפוס
                </button>
            </div>

            <div class="text-center space-y-2">
                <p class="text-sm text-gray-600">
                    <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                        חזור להתחברות
                    </a>
                </p>
                <p class="text-sm text-gray-600">
                    אין לך חשבון עדיין?
                    <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                        הירשם כאן
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>
@endsection