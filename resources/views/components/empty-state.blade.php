@props([
    'title' => __('No data found'),
    'description' => null,
    'icon' => null,
    'action' => null,
    'actionLabel' => null,
])

<div class="text-center py-12 px-4">
    {{-- Optional icon --}}
    @if($icon)
        <div class="mx-auto h-16 w-16 text-content-muted mb-4">
            {{ $icon }}
        </div>
    @else
        {{-- Default illustration using simple shapes --}}
        <div class="mx-auto h-16 w-16 mb-4 opacity-50">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 1.936l-3.182 1.992a2.25 2.25 0 00-1.093 1.776l-1.587 1.654a2.25 2.25 0 00-.735-.363 2.25 2.25 0 00-.621-.872l-.313-.317a2.25 2.25 0 00-.374-.425l-.865-.974a2.25 2.25 0 00-.086-.926l-.768-1.28a2.25 2.25 0 00-.194-.917l-.9-2.002a2.25 2.25 0 00-.09-.546l-.765-2.068a2.25 2.25 0 00-.063-.34l-.353-.874a2.25 2.25 0 00-.041-.247l-.614-1.258a2.25 2.25 0 00.063-.307l.855-1.534a2.25 2.25 0 00.227-.28l.997-1.324a2.25 2.25 0 00.425-.195l1.04-1.342a2.25 2.25 0 00.633.027l.566.359a2.25 2.25 0 00.787.274l1.539 1.358a2.25 2.25 0 00.939.766l.477.286a2.25 2.25 0 00.928.574l1.052.661a2.25 2.25 0 00.765.607l.756.487a2.25 2.25 0 00.568.382l1.12.792a2.25 2.25 0 00.367.233l.8.578.536a2.25 2.25 0 00.268.147l.634.26a2.25 2.25 0 00.187.073l.276.093a2.25 2.25 0 00.107.032" />
            </svg>
        </div>
    @endif

    {{-- Title --}}
    <h3 class="text-lg font-medium text-content mb-2">{{ $title }}</h3>

    {{-- Optional description --}}
    @if($description)
        <p class="text-sm text-content-muted max-w-md mx-auto mb-6">{{ $description }}</p>
    @endif

    {{-- Optional action button --}}
    @if($action && $actionLabel)
        <x-primary-button :href="$action" class="mx-auto inline-flex">
            {{ $actionLabel }}
        </x-primary-button>
    @elseif($action)
        {{-- If action is provided without label, render slot for custom button --}}
        {{ $action }}
    @endif
</div>
