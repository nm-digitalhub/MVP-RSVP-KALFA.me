@props(['status', 'colorClasses', 'icon'])

<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border {{ $colorClasses }} text-[10px] font-black uppercase tracking-wider">
    <x-heroicon-o-{{ $icon }} class="size-3.5" />
    <span>{{ $status->label() }}</span>
</span>
