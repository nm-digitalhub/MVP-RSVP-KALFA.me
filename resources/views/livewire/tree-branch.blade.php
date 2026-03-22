@php

use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public string $label = '';
    public ?string $description = null;
    public ?string $contextLabel = null;
    public int $count = 0;
    public ?string $icon = null;
    public bool $defaultOpen = true;
    public ?string $addLabel = null;
    public ?string $addAction = null;
    public string $tone = 'slate';
    public string $badgeClass = 'border-slate-200 bg-slate-50 text-slate-600';
    public string $countClass = 'bg-slate-100 text-slate-600';
    public string $headerClass = 'bg-slate-50';

    public function mount($label, $count = 0, $icon = null, $defaultOpen = true, $addLabel = null, $addAction = null, $description = null, $tone = 'slate', $contextLabel = null)
    {
        $this->label = $label;
        $this->count = $count;
        $this->icon = $icon;
        $this->defaultOpen = $defaultOpen;
        $this->addLabel = $addLabel;
        $this->addAction = $addAction;
        $this->description = $description;
        $this->tone = $tone;
        $this->contextLabel = $contextLabel;

        $toneClasses = match ($tone) {
            'brand' => [
                'badge' => 'border-brand/20 bg-brand/5 text-brand',
                'count' => 'bg-brand/10 text-brand',
                'header' => 'bg-brand/[0.04]',
            ],
            'sky' => [
                'badge' => 'border-sky-200 bg-sky-50 text-sky-700',
                'count' => 'bg-sky-100 text-sky-700',
                'header' => 'bg-sky-50/70',
            ],
            'emerald' => [
                'badge' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'count' => 'bg-emerald-100 text-emerald-700',
                'header' => 'bg-emerald-50/60',
            ],
            'amber' => [
                'badge' => 'border-amber-200 bg-amber-50 text-amber-700',
                'count' => 'bg-amber-100 text-amber-700',
                'header' => 'bg-amber-50/60',
            ],
            default => [
                'badge' => 'border-slate-200 bg-slate-50 text-slate-600',
                'count' => 'bg-slate-100 text-slate-600',
                'header' => 'bg-slate-50',
            ],
        };

        $this->badgeClass = $toneClasses['badge'];
        $this->countClass = $toneClasses['count'];
        $this->headerClass = $toneClasses['header'];
    }
};
@endphp

<li
    id="{{ 'tree-'.Str::slug($label) }}"
    x-data="{ open: {{ $defaultOpen ? 'true' : 'false' }} }"
    @tree-expand-all.window="open = true"
    @tree-collapse-all.window="open = false"
    class="list-none"
>
    <section>
        <div class="rounded-xl border border-slate-200 px-3 py-2.5 sm:px-5 sm:py-3.5 {{ $headerClass }}">
            <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between">
                <button
                    type="button"
                    @click="open = !open"
                    class="flex min-w-0 flex-1 items-start gap-2.5 rounded-md text-start transition-colors hover:text-slate-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/40"
                    :aria-expanded="open.toString()"
                    aria-controls="{{ 'tree-'.Str::slug($label) }}-items"
                >
                    <span class="mt-0.5 inline-flex shrink-0 items-center gap-0.5 rounded-lg border border-slate-200/90 bg-white/90 p-0.5 shadow-xs">
                        <span class="inline-flex size-6 items-center justify-center rounded-md bg-slate-50 text-slate-500 pointer-coarse:size-8">
                            <x-heroicon-o-chevron-right
                                class="size-3.5 transition-transform duration-200"
                                x-bind:class="{ 'rotate-90': open }"
                            />
                        </span>

                        @if ($icon)
                            <span
                                x-show="$store.productTree.showIcons"
                                class="hidden size-6 items-center justify-center rounded-md text-slate-400 sm:inline-flex sm:size-7"
                            >
                                <x-dynamic-component :component="$icon" class="size-3.5" />
                            </span>
                        @endif
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <span class="truncate text-sm font-semibold tracking-tight text-slate-900">{{ $label }}</span>
                            @if ($count > 0)
                                <span class="inline-flex shrink-0 items-center rounded-sm px-1.5 py-0.5 text-[11px] font-medium {{ $countClass }}">{{ $count }}</span>
                            @endif
                            @if ($contextLabel)
                                <span class="hidden items-center rounded-full border px-2 py-0.5 text-[10px] font-medium uppercase tracking-[0.14em] sm:inline-flex {{ $badgeClass }}">
                                    {{ $contextLabel }}
                                </span>
                            @endif
                        </span>
                        <span class="mt-0.5 hidden text-xs text-slate-500 sm:block">
                            {{ $description ?: trans_choice('{0} No items|{1} :count item|[2,*] :count items', $count, ['count' => number_format($count)]) }}
                        </span>
                    </span>
                </button>

                @if ($addAction && $addLabel)
                    <button
                        type="button"
                        wire:click.stop="$dispatch('{{ $addAction }}')"
                        class="inline-flex min-h-9 shrink-0 items-center justify-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/40"
                    >
                        <x-heroicon-o-plus class="size-4" />
                        <span class="hidden sm:inline">{{ $addLabel }}</span>
                    </button>
                @endif
            </div>
        </div>

        <div
            id="{{ 'tree-'.Str::slug($label) }}-items"
            x-show="open"
            x-collapse
            class="relative pb-3 ps-4 pe-4 sm:ps-5 sm:pe-5"
        >
            <div class="pointer-events-none absolute inset-y-3 start-[1.95rem] w-px bg-gradient-to-b from-slate-300 via-slate-200 to-slate-100 sm:start-[2.45rem]"></div>
            <div class="pointer-events-none absolute start-[1.95rem] top-2 size-2 -translate-x-1/2 rounded-full bg-white ring-2 ring-slate-200 sm:start-[2.45rem]"></div>
            <div class="pointer-events-none absolute bottom-2 start-[1.95rem] h-3 w-px -translate-x-1/2 bg-gradient-to-b from-slate-200 to-transparent sm:start-[2.45rem]"></div>
            <div class="relative ps-6 sm:ps-8">
                <ul class="space-y-1">
                    {{ $slot ?? '' }}
                </ul>
            </div>
        </div>
    </section>
</li>
