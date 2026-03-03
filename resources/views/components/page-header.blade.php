@props(['title', 'subtitle' => null])

<div>
    <h1 class="text-3xl font-bold text-gray-900">
        {{ $title }}
    </h1>

    @if(isset($subtitle) && $subtitle)
        <p class="mt-2 text-sm text-gray-600">
            {{ $subtitle }}
        </p>
    @endif
</div>

<div class="mt-4 border-b border-gray-200"></div>
