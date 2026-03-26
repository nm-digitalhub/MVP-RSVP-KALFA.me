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
            <x-primary-button type="button" wire:click="openCreate" class="inline-flex items-center gap-2 min-h-[44px]">
                <x-heroicon-o-plus class="w-5 h-5" />
                <span class="hidden sm:inline">{{ __('Add invitation') }}</span>
            </x-primary-button>
        @endcan
    </div>

    @can('update', $event)
        @if($showForm)
            <div class="card p-4 sm:p-5 mb-4 sm:mb-5">
                <h3 class="text-base font-semibold text-content mb-3 sm:mb-4">{{ $editingId ? __('Edit invitation') : __('Create invitation') }}</h3>
                <form wire:submit="save" class="space-y-4 max-w-md">
                    <div>
                        <x-ts-select.native
                            id="create_guest"
                            wire:model="createForGuestId"
                            label="{{ __('Link to guest (optional)') }}"
                            :placeholder="__('— No guest —')"
                        >
                            @foreach($guestsWithoutInvitation as $g)
                                <option wire:key="guest-{{ $g->id }}" value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </x-ts-select.native>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                        <x-secondary-button type="button" wire:click="cancelForm">{{ __('Cancel') }}</x-secondary-button>
                    </div>
                </form>
            </div>
        @endif
    @endcan

    @if($event->status !== \App\Enums\EventStatus::Active)
        <div class="mb-4 sm:mb-5">
            <div class="rounded-xl border border-warning/20 bg-warning/10 px-3 sm:px-4 py-3 text-sm text-warning">
                <div class="flex items-start gap-2">
                    <x-heroicon-o-exclamation-circle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <p>{{ __('The RSVP link will be available once the event is active.') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stroke">
                <thead class="bg-surface">
                    <tr>
                        <th scope="col" class="px-3 sm:px-4 py-2 text-end text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">{{ __('Guest') }}</th>
                        <th scope="col" class="px-3 sm:px-4 py-2 text-end text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">{{ __('Status') }}</th>
                        <th scope="col" class="px-3 sm:px-4 py-2 text-end text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted hidden sm:table-cell">{{ __('RSVP link') }}</th>
                        <th scope="col" class="px-3 sm:px-4 py-2 text-end text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-card divide-y divide-stroke">
                    @forelse($invitations as $inv)
                        <tr wire:key="invitation-{{ $inv->id }}" class="data-table-row">
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-content">{{ $inv->guest?->name ?? __('—') }}</td>
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3">
                                <span class="badge text-[9px] sm:text-xs px-1.5 sm:px-2 py-0.5 sm:py-1
                                    @switch($inv->status?->value ?? '')
                                        @case('pending') badge-warning @break
                                        @case('sent') badge-info @break
                                        @case('responded') badge-success @break
                                        @default badge-neutral
                                    @endswitch">
                                    {{ $inv->status?->label() ?? __('—') }}
                                </span>
                            </td>
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm hidden sm:table-cell">
                                <a href="{{ url('rsvp/' . $inv->slug) }}" target="_blank" rel="noopener" class="interactive font-medium text-brand hover:text-brand-hover focus-ring rounded truncate block max-w-[200px]" :title="url('rsvp/' . $inv->slug)">{{ url('rsvp/' . $inv->slug) }}</a>
                            </td>
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-end">
                                @can('update', $event)
                                    @if($inv->status === \App\Enums\InvitationStatus::Pending)
                                        <button type="button" wire:click="markSent({{ $inv->id }})" class="interactive inline-flex min-h-[44px] items-center px-2 sm:px-3 text-sm font-medium text-brand hover:text-brand-hover focus-ring rounded">{{ __('Mark sent') }}</button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 sm:px-4 py-8 sm:py-10 text-center">
                                <div class="flex flex-col items-center justify-center gap-2 sm:gap-3">
                                    <div class="flex size-10 sm:size-12 items-center justify-center rounded-xl bg-surface/50">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0l7.89-5.26a2 2 0 002.22 0l7.89-5.26a2 2 0 002.22 0l7.89 5.26a2 2 0 002.22 0l7.89-5.26M3 16l7.89 5.26a2 2 0 002.22 0l7.89-5.26a2 2 0 002.22 0l7.89 5.26a2 2 0 002.22 0l7.89-5.26a2 2 0 002.22 0l7.89 5.26"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-content">{{ __('No invitations yet.') }}</p>
                                    <p class="text-xs text-content-muted">{{ __('Create invitations to allow guests to RSVP.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
