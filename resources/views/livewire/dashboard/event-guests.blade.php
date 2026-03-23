<div>
    {{-- Back + actions bar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 sm:mb-5">
        <a href="{{ route('dashboard.events.show', $event) }}" class="btn btn-secondary btn-sm focus-ring inline-flex items-center gap-2">
            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <span class="hidden sm:inline">{{ __('Back') }}</span>
        </a>
        @can('update', $event)
            @if(!$showForm)
                <x-primary-button type="button" wire:click="openCreate" class="inline-flex items-center gap-2 min-h-[44px]">
                    <x-heroicon-o-plus class="w-5 h-5" />
                    <span class="hidden sm:inline">{{ __('Add guest') }}</span>
                </x-primary-button>
            @endif
        @endcan
    </div>

    @if($showForm)
        <div class="card p-4 sm:p-5 mb-4 sm:mb-5">
            <h3 class="text-base sm:text-lg font-semibold text-content mb-3 sm:mb-4">{{ $editingId ? __('Edit guest') : __('Add guest') }}</h3>
            <div class="space-y-3 sm:space-y-4 max-w-md">
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
                <div class="flex gap-2 pt-1">
                    <x-primary-button wire:click="save">{{ __('Save') }}</x-primary-button>
                    <x-secondary-button wire:click="cancelForm">{{ __('Cancel') }}</x-secondary-button>
                </div>
            </div>
        </div>
    @endif

    {{-- Import --}}
    @can('update', $event)
        <div class="card p-4 sm:p-5 mb-4 sm:mb-5">
            <h3 class="text-sm font-semibold text-content mb-2">{{ __('Import from CSV') }}</h3>
            <p class="text-sm text-content-muted mb-3">{{ __('CSV columns: name (or שם), email, phone, notes') }}</p>
            <form wire:submit="import" class="flex flex-wrap items-end gap-3">
                <div class="w-full sm:max-w-md">
                    <x-ts-input
                        id="guest-import-file"
                        type="file"
                        wire:model="importFile"
                        accept=".csv,.txt"
                        label="{{ __('CSV file') }}"
                        hint="{{ __('CSV columns: name (or שם), email, phone, notes') }}"
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

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stroke">
                <thead class="bg-surface">
                    <tr>
                        <th scope="col" class="px-3 sm:px-4 py-2 text-end text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">{{ __('Name') }}</th>
                        <th scope="col" class="px-3 sm:px-4 py-2 text-end text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted hidden sm:table-cell">{{ __('Email') }}</th>
                        <th scope="col" class="px-3 sm:px-4 py-2 text-end text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted hidden sm:table-cell">{{ __('Phone') }}</th>
                        <th scope="col" class="px-3 sm:px-4 py-2 text-end text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">{{ __('Group') }}</th>
                        <th scope="col" class="px-3 sm:px-4 py-2 text-end text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-card divide-y divide-stroke">
                    @forelse($guests as $guest)
                        <tr wire:key="guest-{{ $guest->id }}" class="data-table-row">
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm font-medium text-content">{{ $guest->name }}</td>
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-content-muted hidden sm:table-cell">{{ $guest->email ?? '—' }}</td>
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-content-muted hidden sm:table-cell">{{ $guest->phone ?? '—' }}</td>
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-content-muted">{{ $guest->group_name ?? '—' }}</td>
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-end">
                                @can('update', $guest)
                                    <button type="button" wire:click="openEdit({{ $guest->id }})" class="interactive inline-flex min-h-[44px] items-center px-2 text-sm font-medium text-brand hover:text-brand-hover focus-ring rounded">{{ __('Edit') }}</button>
                                @endcan
                                @can('delete', $guest)
                                    <button type="button" wire:click="deleteGuest({{ $guest->id }})" wire:confirm="{{ __('Delete this guest?') }}" class="interactive inline-flex min-h-[44px] items-center px-2 text-sm font-medium text-danger hover:text-danger-hover focus-ring rounded ms-2">{{ __('Delete') }}</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 sm:px-4 py-8 sm:py-10 text-center">
                                <div class="flex flex-col items-center justify-center gap-2 sm:gap-3">
                                    <div class="flex size-10 sm:size-12 items-center justify-center rounded-xl bg-surface/50">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-content">{{ __('No guests yet.') }}</p>
                                    <p class="text-xs text-content-muted">{{ __('Add guests to your event.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
