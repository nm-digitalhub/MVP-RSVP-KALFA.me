<div>
    <div class="mb-4">
        <a href="{{ route('dashboard.events.show', $event) }}" class="inline-flex min-h-[44px] items-center rounded-lg px-2 text-sm font-medium text-gray-500 transition-colors duration-200 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">&larr; {{ __('Back to event') }}</a>
    </div>

    @can('update', $event)
        <div class="mb-4">
            <button type="button" wire:click="save" class="inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-brand hover:bg-brand-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 transition-colors duration-200 cursor-pointer">{{ __('Save assignments') }}</button>
        </div>
    @endcan

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-end text-xs font-medium text-gray-500 uppercase">{{ __('Guest') }}</th>
                    <th class="px-4 py-2 text-end text-xs font-medium text-gray-500 uppercase">{{ __('Table') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($guests as $guest)
                    <tr wire:key="guest-{{ $guest->id }}">
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $guest->name }}</td>
                        <td class="px-4 py-2 text-sm">
                            @can('update', $event)
                                <div class="max-w-[220px]">
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
                        <td colspan="2" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No guests. Add guests first.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
