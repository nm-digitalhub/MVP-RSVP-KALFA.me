<x-pulse::card :cols="$cols ?? 3" :rows="$rows ?? 2" :class="$class" :expand="$expand">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                RSVP Operations
            </h2>
            @if($runAt)
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $time }} ago
                </span>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- RSVP Responses --}}
        <div>
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">RSVP Responses</h3>
            @if(!empty($data['responses']['by_type']))
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['yes' => 'Attending', 'no' => 'Declining', 'maybe' => 'Maybe'] as $key => $label)
                        <div class="text-center p-2 bg-gray-50 dark:bg-gray-800 rounded">
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $data['responses']['by_type'][$key] ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">No RSVP responses recorded.</p>
            @endif
        </div>

        {{-- Invitations --}}
        <div>
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Invitations Sent</h3>
            <div class="flex items-center justify-between p-2 bg-blue-50 dark:bg-blue-900/20 rounded">
                <span class="text-sm text-gray-700 dark:text-gray-300">Total</span>
                <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                    {{ $data['invitations']['total'] ?? 0 }}
                </span>
            </div>
        </div>

        {{-- Seating Assignments --}}
        <div>
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Seating Assignments</h3>
            <div class="flex items-center justify-between p-2 bg-green-50 dark:bg-green-900/20 rounded">
                <span class="text-sm text-gray-700 dark:text-gray-300">Total</span>
                <span class="text-lg font-bold text-green-600 dark:text-green-400">
                    {{ $data['seating']['total'] ?? 0 }}
                </span>
            </div>
        </div>
    </div>
</x-pulse::card>
