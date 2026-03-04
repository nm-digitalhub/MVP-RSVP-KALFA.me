<div>
    <div class="mb-4">
        <a href="{{ route('dashboard.events.show', $event) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('Back to event') }}</a>
    </div>

    @can('update', $event)
        <div class="mb-4">
            <button type="button" wire:click="save" class="inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-colors duration-200 cursor-pointer">{{ __('Save assignments') }}</button>
        </div>
    @endcan

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Guest') }}</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Table') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($guests as $guest)
                    <tr wire:key="guest-{{ $guest->id }}">
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $guest->name }}</td>
                        <td class="px-4 py-2 text-sm">
                            @can('update', $event)
                                <select wire:model="assignments.{{ $guest->id }}" class="block w-full max-w-[200px] rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                                    <option value="">{{ __('— None —') }}</option>
                                    @foreach($tables as $t)
                                        <option wire:key="table-{{ $t->id }}" value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
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
