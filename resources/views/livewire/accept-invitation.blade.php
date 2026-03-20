<div class="min-h-screen flex flex-col sm:justify-center items-center p-4 sm:p-6 bg-gradient-to-br from-indigo-50 via-white to-purple-50">
    <div class="w-full sm:max-w-lg mt-6 px-8 py-10 bg-white/80 backdrop-blur-xl shadow-2xl shadow-indigo-900/10 overflow-hidden rounded-3xl border border-white/50 animate-in fade-in zoom-in-95 duration-700">
        <div class="text-center mb-10">
            <x-auth-logo />

            <div class="relative inline-flex mt-6 mb-6">
                <div class="absolute inset-0 bg-indigo-200 rounded-full blur-2xl opacity-50 animate-pulse"></div>
                <div class="relative inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-gradient-to-br from-indigo-600 to-purple-700 text-white shadow-xl rotate-3 hover:rotate-0 transition-transform duration-300">
                    <x-heroicon-o-user-group class="w-10 h-10" />
                </div>
            </div>
            
            <h2 class="text-3xl font-black text-gray-900 tracking-tight leading-tight">{{ __('Join the Team') }}</h2>
            <div class="mt-4 p-4 rounded-2xl bg-gray-50/50 border border-gray-100 italic">
                <p class="text-lg text-gray-700">
                    {{ __('You have been invited to join **:organization** as a **:role**.', [
                        'organization' => $invitation->organization->name,
                        'role' => __($invitation->role->value)
                    ]) }}
                </p>
            </div>
        </div>

        <div class="space-y-6">
            <button 
                wire:click="accept" 
                class="group relative w-full flex items-center justify-center gap-3 py-4 px-6 bg-gray-900 text-white font-bold rounded-2xl hover:bg-brand active:scale-[0.98] transition-all duration-300 shadow-xl shadow-gray-900/20 hover:shadow-indigo-500/30 overflow-hidden cursor-pointer"
            >
                <span class="relative z-10">
                    @auth
                        {{ __('Accept Invitation & Enter') }}
                    @else
                        {{ __('Create Account & Join') }}
                    @endauth
                </span>
                <x-heroicon-o-arrow-right class="w-5 h-5 relative z-10 group-hover:translate-x-1 transition-transform" />
                <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </button>

            @guest
                <div class="relative">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-gray-100"></div>
                    </div>
                    <div class="relative flex justify-center text-sm font-medium uppercase tracking-widest">
                        <span class="bg-white px-4 text-gray-400">{{ __('Or') }}</span>
                    </div>
                </div>

                <div class="text-center">
                    <p class="text-gray-500 font-medium">
                        {{ __('Already have an account?') }}
                        <a href="{{ route('login', ['invitation' => $token]) }}" class="inline-flex items-center gap-1 text-brand hover:text-indigo-700 font-bold transition-colors">
                            {{ __('Log in here') }}
                            <x-heroicon-o-chevron-right class="w-4 h-4" />
                        </a>
                    </p>
                </div>
            @endguest
        </div>

        <div class="mt-10 pt-8 border-t border-gray-100 text-center">
            <div class="flex items-center justify-center gap-3 text-xs font-medium uppercase tracking-widest text-gray-400">
                <span>{{ __('Powered by') }}</span>
                <x-kalfa-app-icon class="h-7 w-7" alt="" />
                <span>{{ config('app.name') }}</span>
            </div>
        </div>
    </div>
</div>
