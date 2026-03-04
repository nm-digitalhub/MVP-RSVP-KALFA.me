@props(['title', 'subtitle' => null])

<header>
    <h1 id="page-title" @if(isset($subtitle) && $subtitle) aria-describedby="page-subtitle" @endif class="text-2xl font-bold leading-tight tracking-tight text-gray-900 sm:text-3xl">
        {{ $title }}
    </h1>

    @if(isset($subtitle) && $subtitle)
        <p id="page-subtitle" class="mt-2 text-sm leading-relaxed text-gray-600 rtl:text-end">
            {{ $subtitle }}
        </p>
    @endif
</header>

<div class="mt-4 border-b border-gray-200" aria-hidden="true"></div>
