<div>
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <a href="{{ route('dashboard.events.show', $event) }}" class="inline-flex min-h-[44px] items-center rounded-lg px-2 text-sm font-medium text-gray-500 transition-colors duration-200 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">&larr; {{ __('Back to event') }}</a>
        @can('update', $event)
            @if(!$showForm)
                <div class="flex gap-2">
                    <x-primary-button type="button" wire:click="openCreate">{{ __('Add guest') }}</x-primary-button>
                </div>
            @endif
        @endcan
    </div>

    @if($showForm)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $editingId ? __('Edit guest') : __('Add guest') }}</h3>
            <div class="space-y-3 max-w-md">
                <div>
                    <x-ts-input id="guest_name" wire:model="name" label="{{ __('Name') }}" />
                </div>
                <div>
                    <x-ts-input id="guest_email" type="email" wire:model="email" label="{{ __('Email') }}" />
                </div>
                <div>
                    <x-ts-input id="guest_phone" wire:model="phone" label="{{ __('Phone') }}" />
                </div>
                <div>
                    <x-ts-input id="guest_group" wire:model="group_name" label="{{ __('Group name') }}" />
                </div>
                <div>
                    <x-ts-textarea id="guest_notes" wire:model="notes" rows="2" label="{{ __('Notes') }}" />
                </div>
                <div class="flex gap-2">
                    <x-primary-button wire:click="save">{{ __('Save') }}</x-primary-button>
                    <x-secondary-button wire:click="cancelForm">{{ __('Cancel') }}</x-secondary-button>
                </div>
            </div>
        </div>
    @endif

    {{-- Import --}}
    @can('update', $event)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <h3 class="text-sm font-medium text-gray-900 mb-2">{{ __('Import from CSV') }}</h3>
            <p class="text-sm text-gray-500 mb-2">{{ __('CSV columns: name (or שם), email, phone, notes') }}</p>
            <form wire:submit="import" class="flex flex-wrap items-end gap-3">
                <div class="w-full sm:max-w-md">
                    <x-ts-input
                        id="guest-import-file"
                        type="file"
                        wire:model="importFile"
                        accept=".csv,.txt"
                        label="{{ __('CSV file') }}"
                        hint="{{ __('CSV columns: name (or שם), email, phone, notes') }}"
                        class="mt-1"
                    />
                    <div wire:loading wire:target="importFile" class="mt-2">
                        <x-ts-alert color="primary" light text="{{ __('Uploading...') }}" />
                    </div>
                    <x-input-error :messages="$errors->get('importFile')" class="mt-1" />
                </div>
                <x-primary-button type="submit">{{ __('Import') }}</x-primary-button>
            </form>
        </div>
    @endcan

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-end text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                        <th class="px-4 py-2 text-end text-xs font-medium text-gray-500 uppercase">{{ __('Email') }}</th>
                        <th class="px-4 py-2 text-end text-xs font-medium text-gray-500 uppercase">{{ __('Phone') }}</th>
                        <th class="px-4 py-2 text-end text-xs font-medium text-gray-500 uppercase">{{ __('Group') }}</th>
                        <th class="px-4 py-2 text-end text-xs font-medium text-gray-500 uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($guests as $guest)
                        <tr wire:key="guest-{{ $guest->id }}">
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $guest->name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">{{ $guest->email ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">{{ $guest->phone ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">{{ $guest->group_name ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm">
                                @can('update', $guest)
                                    <button type="button" wire:click="openEdit({{ $guest->id }})" class="min-h-[44px] inline-flex items-center px-2 text-brand hover:text-indigo-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1 rounded cursor-pointer transition-colors duration-200">{{ __('Edit') }}</button>
                                @endcan
                                @can('delete', $guest)
                                    <button type="button" wire:click="deleteGuest({{ $guest->id }})" wire:confirm="{{ __('Delete this guest?') }}" class="min-h-[44px] inline-flex items-center px-2 text-red-600 hover:text-red-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-1 rounded cursor-pointer transition-colors duration-200">{{ __('Delete') }}</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No guests yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
