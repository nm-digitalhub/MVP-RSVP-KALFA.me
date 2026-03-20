<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12" role="main" aria-label="{{ __('Team Management') }}">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ __('Team Management') }}</h1>
            <p class="mt-2 text-lg text-gray-600">{{ __('Manage your organization members and pending invitations.') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="px-4 py-2 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center gap-3">
                <span class="text-sm font-medium text-indigo-700">{{ __('Active Members') }}</span>
                <span class="text-xl font-bold text-indigo-900">{{ $members->count() }}</span>
            </div>
        </div>
    </div>

    @session('success')
        <div class="mb-8 p-4 rounded-2xl bg-green-50/90 border border-green-200/60 text-green-800 text-sm flex items-center gap-3 backdrop-blur-sm" role="alert">
            <div class="shrink-0 w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                <x-heroicon-o-check-circle class="w-5 h-5 text-green-600" />
            </div>
            <span class="font-medium">{{ $value }}</span>
        </div>
    @endsession

    @session('error')
        <div class="mb-8 p-4 rounded-2xl bg-red-50/90 border border-red-200/60 text-red-800 text-sm flex items-center gap-3 backdrop-blur-sm" role="alert">
            <div class="shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                <x-heroicon-o-exclamation-circle class="w-5 h-5 text-red-600" />
            </div>
            <span class="font-medium">{{ $value }}</span>
        </div>
    @endsession

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 items-start">
        {{-- Invite Form Card --}}
        <div class="xl:col-span-1 sticky top-24">
            <div class="bg-white/95 rounded-3xl shadow-xl shadow-gray-900/5 border border-gray-200/70 overflow-hidden backdrop-blur-sm">
                <div class="px-8 py-6 border-b border-gray-100 bg-gradient-to-br from-gray-50/50 to-white">
                    <h2 class="text-xl font-bold text-gray-900">{{ __('Invite New Member') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Send an email invitation to join your team.') }}</p>
                </div>
                <div class="p-8">
                    <form wire:submit="inviteMember" class="space-y-6">
                        <div>
                            <x-input-label for="invite-email" :value="__('Email Address')" class="text-sm font-semibold text-gray-700" />
                            <x-text-input id="invite-email" type="email" wire:model="email" class="mt-2 block w-full bg-gray-50/50 focus:bg-white transition-colors" placeholder="name@example.com" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="invite-role" :value="__('Role')" class="text-sm font-semibold text-gray-700" />
                            <select id="invite-role" wire:model="role" class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand focus:ring-4 focus:ring-brand/10 transition-all">
                                <option value="member">{{ __('Member') }}</option>
                                <option value="admin">{{ __('Admin') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>
                        <div class="pt-2">
                            <x-primary-button type="submit" class="w-full justify-center py-3 rounded-xl shadow-lg shadow-indigo-200 hover:shadow-indigo-300 transition-all active:scale-[0.98]">
                                <x-heroicon-o-paper-airplane class="w-4 h-4 me-2" />
                                {{ __('Send Invitation') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Main Lists Section --}}
        <div class="xl:col-span-2 flex flex-col gap-8">
            {{-- Active Members Table --}}
            <div class="bg-white/95 rounded-3xl shadow-xl shadow-gray-900/5 border border-gray-200/70 overflow-hidden backdrop-blur-sm">
                <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-gray-50/50 to-white">
                    <h2 class="text-xl font-bold text-gray-900">{{ __('Team Members') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead>
                            <tr class="bg-gray-50/30">
                                <th class="px-8 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-widest">{{ __('Member') }}</th>
                                <th class="px-8 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-widest">{{ __('Role') }}</th>
                                <th class="px-8 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-widest"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($members as $member)
                                <tr wire:key="member-{{ $member->id }}" class="group hover:bg-indigo-50/30 transition-colors duration-150">
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <div class="flex items-center gap-4">
                                            <div class="shrink-0 w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg shadow-md group-hover:scale-110 transition-transform duration-200">
                                                {{ substr($member->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="text-base font-bold text-gray-900">{{ $member->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $member->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        @if($member->id === auth()->id() || $member->pivot->role->value === 'owner')
                                            <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200 uppercase tracking-wider">
                                                {{ __($member->pivot->role->value) }}
                                            </span>
                                        @else
                                            <select 
                                                wire:change="updateRole({{ $member->id }}, $event.target.value)"
                                                class="text-sm rounded-xl border-gray-200 py-1.5 pe-10 focus:ring-4 focus:ring-brand/10 focus:border-brand transition-all font-medium text-gray-700"
                                            >
                                                <option value="member" @selected($member->pivot->role->value === 'member')>{{ __('Member') }}</option>
                                                <option value="admin" @selected($member->pivot->role->value === 'admin')>{{ __('Admin') }}</option>
                                            </select>
                                        @endif
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap text-end">
                                        @if($member->id !== auth()->id() && $member->pivot->role->value !== 'owner')
                                            <button 
                                                type="button" 
                                                wire:click="removeMember({{ $member->id }})" 
                                                wire:confirm="{{ __('Are you sure you want to remove this member?') }}"
                                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-red-600 hover:bg-red-50 rounded-xl transition-all active:scale-95"
                                            >
                                                <x-heroicon-o-user-minus class="w-4 h-4" />
                                                {{ __('Remove') }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pending Invitations Table --}}
            @if($invitations->isNotEmpty())
                <div class="bg-white/95 rounded-3xl shadow-xl shadow-gray-900/5 border border-gray-200/70 overflow-hidden backdrop-blur-sm animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div class="px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-amber-50/30 to-white">
                        <h2 class="text-xl font-bold text-gray-900">{{ __('Pending Invitations') }}</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead>
                                <tr class="bg-gray-50/30">
                                    <th class="px-8 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-widest">{{ __('Email') }}</th>
                                    <th class="px-8 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-widest">{{ __('Role') }}</th>
                                    <th class="px-8 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-widest">{{ __('Expires') }}</th>
                                    <th class="px-8 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-widest"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($invitations as $invitation)
                                    <tr wire:key="invitation-{{ $invitation->id }}" class="hover:bg-amber-50/30 transition-colors duration-150">
                                        <td class="px-8 py-5 whitespace-nowrap text-base font-medium text-gray-900">{{ $invitation->email }}</td>
                                        <td class="px-8 py-5 whitespace-nowrap">
                                            <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-700 ring-1 ring-amber-200 uppercase tracking-wider">
                                                {{ __($invitation->role->value) }}
                                            </span>
                                        </td>
                                        <td class="px-8 py-5 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-o-clock class="w-4 h-4" />
                                                {{ $invitation->expires_at->diffForHumans() }}
                                            </div>
                                        </td>
                                        <td class="px-8 py-5 whitespace-nowrap text-end">
                                            <button 
                                                type="button" 
                                                wire:click="cancelInvitation({{ $invitation->id }})" 
                                                class="px-4 py-2 text-sm font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-all"
                                            >
                                                {{ __('Cancel') }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
