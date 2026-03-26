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
            <x-primary-button type="button" wire:click="save">{{ __('Save assignments') }}</x-primary-button>
        @endcan
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stroke">
                <thead class="bg-surface">
                    <tr>
                        <th scope="col" class="px-3 sm:px-4 py-2 text-end text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">{{ __('Guest') }}</th>
                        <th scope="col" class="px-3 sm:px-4 py-2 text-end text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-content-muted">{{ __('Table') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-card divide-y divide-stroke">
                    @forelse($guests as $guest)
                        <tr wire:key="guest-{{ $guest->id }}" class="data-table-row">
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-content">{{ $guest->name }}</td>
                            <td class="px-3 sm:px-4 py-2.5 sm:py-3 text-sm">
                                @can('update', $event)
                                    <div class="max-w-[180px] sm:max-w-[220px]">
                                        <x-ts-select.native wire:model="assignments.{{ $guest->id }}" dusk="seat-assignment-select-{{ $guest->id }}">
                                            <option value="">{{ __('— None —') }}</option>
                                            @foreach($tables as $t)
                                                <option wire:key="table-{{ $t->id }}" value="{{ $t->id }}">{{ $t->name }}</option>
                                            @endforeach
                                        </x-ts-select.native>
                                    </div>
                                @else
                                    @php $a = $assignments[$guest->id] ?? null; $t = $tables->firstWhere('id', $a); @endphp
                                    {{ $t?->name ?? '—' }}
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-3 sm:px-4 py-8 sm:py-10 text-center">
                                <div class="flex flex-col items-center justify-center gap-2 sm:gap-3">
                                    <div class="flex size-10 sm:size-12 items-center justify-center rounded-xl bg-surface/50">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-content-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-content">{{ __('No guests.') }}</p>
                                    <p class="text-xs text-content-muted">{{ __('Add guests to create seat assignments.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
