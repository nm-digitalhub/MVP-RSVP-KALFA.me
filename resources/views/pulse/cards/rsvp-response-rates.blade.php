<x-pulse::card
    :cols="$cols ?? 3"
    :rows="$rows ?? 2"
    :class="$class"
    :expand="$expand ?? false"
>
    <x-slot name="title">
        RSVP Response Rates
    </x-slot>

    <x-slot name="actions">
        <x-pulse::period-selector />
    </x-slot>

    <div class="grid grid-cols-3 gap-4">
        @foreach(['attending', 'declining', 'maybe'] as $response)
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="text-2xl font-bold {{ $response === 'attending' ? 'text-green-600' : ($response === 'declining' ? 'text-red-600' : 'text-yellow-600') }}">
                    {{ $rsvpData[$response] ?? 0 }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1 capitalize">
                    {{ $response }}
                </div>
            </div>
        @endforeach
    </div>

    @if($time ?? null)
        <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
            Data from {{ $time }}
        </div>
    @endif
</x-pulse::card>
