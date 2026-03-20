<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <a href="{{ route('system.users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('Back to users') }}</a>
            <div class="flex items-center gap-2 mt-1">
                <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
                @if($user->is_disabled ?? false)
                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">{{ __('Disabled') }}</span>
                @endif
                @if($user->is_system_admin)
                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">{{ __('System Admin') }}</span>
                @endif
            </div>
            <p class="text-gray-600">{{ $user->email }}</p>
        </div>
    </div>

    @session('success')
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ $value }}</span>
        </div>
    @endsession

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">{{ __('Organizations') }}</p>
            <p class="mt-1 text-xl font-semibold text-gray-900">{{ $user->organizations()->count() }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">{{ __('Owned organizations') }}</p>
            <p class="mt-1 text-xl font-semibold text-gray-900">{{ $user->ownedOrganizations()->count() }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">{{ __('Registration date') }}</p>
            <p class="mt-1 text-gray-900">{{ $user->created_at?->format('Y-m-d H:i') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">{{ __('Last login') }}</p>
            <p class="mt-1 text-gray-900">{{ $user->last_login_at?->format('Y-m-d H:i') ?? '—' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">{{ __('Organizations list & Subscriptions') }}</h2>
            <ul class="space-y-3 text-sm text-gray-700">
                @forelse($user->organizations as $org)
                    <li wire:key="org-{{ $org->id }}" class="border-b border-gray-100 last:border-0 pb-2 last:pb-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <a href="{{ route('system.organizations.show', $org) }}" class="text-brand font-medium hover:text-indigo-900">{{ $org->name }}</a>
                                <span class="text-gray-500 text-xs">({{ is_object($org->pivot->role) ? $org->pivot->role->value : $org->pivot->role }})</span>
                                <button wire:click="syncOrganization({{ $org->id }})" wire:loading.attr="disabled" class="ml-2 text-gray-400 hover:text-brand" title="{{ __('Sync from SUMIT') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </button>
                            </div>
                            <div class="text-right">
                                @php $sub = $organizationSubscriptions[$org->id] ?? null; @endphp
                                @if($sub)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ ucfirst($sub->status) }}</span>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        {{ number_format((float)$sub->amount, 2) }} {{ $sub->currency }}
                                        @if($sub->next_charge_at)
                                            <br>Next: {{ \Carbon\Carbon::parse($sub->next_charge_at)->format('Y-m-d') }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs italic">{{ __('No active subscription') }}</span>
                                @endif
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="text-gray-500">{{ __('None') }}</li>
                @endforelse
            </ul>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">{{ __('Events (in member orgs)') }}</h2>
            <p class="text-gray-900 font-semibold text-2xl">{{ $eventsCount ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-2">{{ __('Usage data based on current organization memberships.') }}</p>
        </div>
    </div>

    {{-- Admin actions --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-medium text-gray-900">{{ __('Admin actions') }}</h2>
        </div>
        <div class="p-4 flex flex-wrap gap-2">
            @if($user->id !== auth()->id())
                @if($user->is_system_admin)
                    <button type="button" wire:click="requestAction('demoteSystemAdmin')" class="rounded-md bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700">{{ __('Demote system admin') }}</button>
                @else
                    <button type="button" wire:click="requestAction('promoteToSystemAdmin')" class="rounded-md bg-brand px-3 py-2 text-sm font-medium text-white hover:bg-brand-hover">{{ __('Promote to system admin') }}</button>
                @endif
            @endif
            @if(! ($user->is_disabled ?? false))
                <button type="button" wire:click="requestAction('disableUser')" class="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('Disable user') }}</button>
            @endif
            <button type="button" wire:click="requestAction('forcePasswordReset')" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('Force password reset') }}</button>
            <button type="button" wire:click="requestAction('invalidateSessions')" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('Invalidate sessions') }}</button>
        </div>
    </div>

    {{-- Password confirmation --}}
    @if($pendingAction)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" role="dialog">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('Confirm password') }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Enter your password to confirm this action.') }}</p>
                <div class="mt-4">
                    <input type="password" wire:model="confirmPassword" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm px-3 py-2" placeholder="{{ __('Password') }}" autofocus />
                    @error('confirmPassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="cancelConfirm" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Cancel') }}</button>
                    <button type="button" wire:click="confirmAndExecute" class="rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-hover">{{ __('Confirm') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
