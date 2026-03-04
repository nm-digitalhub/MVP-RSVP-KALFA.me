<div class="max-w-7xl mx-auto space-y-6" role="main" aria-label="{{ __('Organization details') }}">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <a href="{{ route('system.organizations.index') }}" class="inline-flex items-center gap-1 min-h-[44px] px-4 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2" aria-label="{{ __('Back to organizations') }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7 7"/></svg>
                {{ __('Back to organizations') }}
            </a>
            <div class="flex items-center gap-2 mt-1">
                <h1 class="text-2xl font-bold text-gray-900">{{ $organization->name }}</h1>
                @if($organization->is_suspended)
                    <span class="inline-flex px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-red-100/90 border border-red-200 text-red-800">{{ __('Suspended') }}</span>
                @else
                    <span class="inline-flex px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-green-100/90 border border-green-200 text-green-800">{{ __('Active') }}</span>
                @endif
            </div>
        </div>
        <form action="{{ route('system.impersonate', $organization) }}" method="POST" class="inline">
            @csrf
            <x-primary-button type="submit">{{ __('Impersonate') }}</x-primary-button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white/95 rounded-2xl shadow-lg shadow-gray-900/5 border border-gray-200/70 p-5 backdrop-blur-sm">
            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">{{ __('Owner') }}</p>
            <p class="mt-2 text-gray-900 font-medium text-lg">{{ $owner?->name ?? __('—') }}</p>
            @if($owner)
                <p class="text-sm text-gray-500">{{ $owner->email }}</p>
            @endif
        </div>
        <div class="bg-white/95 rounded-2xl shadow-lg shadow-gray-900/5 border border-gray-200/70 p-5 backdrop-blur-sm">
            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">{{ __('Members') }}</p>
            <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $membersCount }}</p>
        </div>
        <div class="bg-white/95 rounded-2xl shadow-lg shadow-gray-900/5 border border-gray-200/70 p-5 backdrop-blur-sm">
            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">{{ __('Created') }}</p>
            <p class="mt-2 text-gray-900 font-medium">{{ $organization->created_at?->format('Y-m-d H:i') }}</p>
        </div>
        <div class="bg-white/95 rounded-2xl shadow-lg shadow-gray-900/5 border border-gray-200/70 p-5 backdrop-blur-sm">
            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">{{ __('Last activity') }}</p>
            <p class="mt-2 text-gray-900 font-medium">{{ $organization->updated_at?->format('Y-m-d H:i') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl shadow-lg shadow-gray-900/5 border border-gray-200/70 p-5 backdrop-blur-sm">
            <h2 class="text-sm font-semibold text-gray-600 uppercase tracking-wider mb-2">{{ __('Billing status') }}</h2>
            <p class="text-gray-600 text-sm">{{ __('Placeholder until OfficeGuy ready.') }}</p>
        </div>
        <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl shadow-lg shadow-gray-900/5 border border-gray-200/70 p-5 backdrop-blur-sm">
            <h2 class="text-sm font-semibold text-gray-600 uppercase tracking-wider mb-2">{{ __('Plan / Subscription') }}</h2>
            <p class="text-gray-600 text-sm">{{ __('Plan name') }}: {{ __('—') }}</p>
            <p class="text-gray-600 text-sm mt-1">{{ __('Subscription status') }}: {{ __('—') }}</p>
        </div>
    </div>

    <div class="bg-white/95 rounded-2xl shadow-lg shadow-gray-900/5 border border-gray-200/70 overflow-hidden backdrop-blur-sm">
        <div class="px-6 py-4 border-b border-gray-200/80 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Events') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200/80">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100/50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">{{ __('Name') }}</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">{{ __('Date') }}</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200/80">
                    @forelse($events as $event)
                        <tr wire:key="event-{{ $event->id }}" class="hover:bg-gray-50/50 transition-colors duration-150 ease-out">
                            <td class="px-4 py-2.5 text-sm text-gray-900">{{ $event->name }}</td>
                            <td class="px-4 py-2.5 text-sm text-gray-700">{{ $event->event_date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-2.5 text-sm font-medium">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs {{ $event->status?->value === 'active' ? 'bg-green-100/90 border border-green-200 text-green-800' : 'bg-gray-100 border border-gray-200 text-gray-700' }}">
                                    {{ $event->status?->value ? __($event->status->value) : __('—') }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-sm">
                                @if($event->status !== \App\Enums\EventStatus::Active)
                                    <button type="button" wire:click="requestAction('setEventActive', null, {{ $event->id }})" class="min-h-[44px] inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500 focus-visible:ring-offset-2 transition-colors duration-200 cursor-pointer">
                                        {{ __('Set to active') }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No events.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200/80">
            {{ $events->links() }}
        </div>
    </div>

    {{-- Admin actions --}}
    <div class="bg-white/95 rounded-2xl shadow-lg shadow-gray-900/5 border border-gray-200/70 overflow-hidden backdrop-blur-sm">
        <div class="px-6 py-4 border-b border-gray-200/80 bg-gradient-to-r from-amber-50 to-white">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Admin actions') }}</h2>
        </div>
        <div class="p-5 space-y-4">
            @if($organization->is_suspended)
                <button type="button" wire:click="requestAction('activate')" class="w-full inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 active:bg-green-800 border border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500 focus-visible:ring-offset-2 transition-colors duration-200">{{ __('Activate organization') }}</button>
            @else
                <button type="button" wire:click="requestAction('suspend')" class="w-full inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 rounded-lg text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 active:bg-amber-800 border border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition-colors duration-200">{{ __('Suspend organization') }}</button>
            @endif
            @if($members->count() > 1)
                <div class="pt-3 border-t border-gray-200">
                    <p class="text-sm font-semibold text-gray-700 mb-3">{{ __('Transfer ownership') }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($members as $m)
                            @if($m->id !== $owner?->id)
                                <x-secondary-button type="button" wire:key="member-{{ $m->id }}" wire:click="requestAction('transferOwnership', {{ $m->id }})">{{ $m->name }}</x-secondary-button>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="pt-3 border-t border-gray-200 flex flex-wrap gap-2">
                <x-danger-button type="button" wire:click="requestAction('forceDelete')">{{ __('Force delete organization') }}</x-danger-button>
                <x-secondary-button type="button" wire:click="requestAction('resetData')" class="!border-red-300 !text-red-700 hover:!bg-red-50 hover:!border-red-400">{{ __('Reset data (danger zone)') }}</x-secondary-button>
            </div>
        </div>
    </div>

    {{-- Password confirmation modal --}}
    @if($pendingAction)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4" role="dialog" aria-modal="true" aria-labelledby="confirm-password-title" aria-describedby="confirm-password-desc">
            <div class="bg-white rounded-xl shadow-xl border border-gray-200 max-w-md w-full p-6">
                <h3 id="confirm-password-title" class="text-lg font-semibold text-gray-900 mb-2">{{ __('Confirm password') }}</h3>
                <p id="confirm-password-desc" class="mt-1 text-sm text-gray-600">{{ __('Enter your password to confirm this action.') }}</p>
                <div class="mt-4">
                    <label for="confirm-password-input" class="sr-only">{{ __('Password') }}</label>
                    <input id="confirm-password-input" type="password" wire:model="confirmPassword" class="mt-1 block w-full min-h-[44px] rounded-lg border border-gray-300 px-4 py-2.5 text-sm rtl:text-end shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200" placeholder="{{ __('Password') }}" autofocus aria-invalid="{{ $errors->has('confirmPassword') ? 'true' : 'false' }}" @if($errors->has('confirmPassword')) aria-describedby="confirm-password-error" @endif />
                    @error('confirmPassword')
                        <p id="confirm-password-error" class="mt-2 text-sm text-red-600 rtl:text-end" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <x-secondary-button type="button" wire:click="cancelConfirm">{{ __('Cancel') }}</x-secondary-button>
                    <x-primary-button type="button" wire:click="confirmAndExecute">{{ __('Confirm') }}</x-primary-button>
                </div>
            </div>
        </div>
    @endif
</div>
