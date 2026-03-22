@php

use Livewire\Component;

new class extends Component
{
    public $nodeId = null;
    public $label = '';
    public $identifier = null;
    public $status = 'active';
    public $type = 'task';
    public $description = '';
    public $lastComment = null;
    public $meta = [];
    public $draggable = false;
    public $isActive = false;

    public function requestEdit(): void
    {
        match ($this->type) {
            'plan' => $this->dispatch('tree:open-edit-plan', planId: $this->nodeId),
            'limit' => $this->dispatch('tree:open-edit-limit', limitId: $this->nodeId),
            'feature' => $this->dispatch('tree:open-edit-feature', featureId: $this->nodeId),
            'entitlement' => $this->dispatch('tree:open-edit-entitlement', entitlementId: $this->nodeId),
            default => null,
        };
    }

    public function requestDelete(): void
    {
        match ($this->type) {
            'plan' => $this->dispatch('tree:delete-plan', planId: $this->nodeId),
            'limit' => $this->dispatch('tree:delete-limit', limitId: $this->nodeId),
            'feature' => $this->dispatch('tree:delete-feature', featureId: $this->nodeId),
            'entitlement' => $this->dispatch('tree:delete-entitlement', entitlementId: $this->nodeId),
            default => null,
        };
    }

    public function requestToggle(): void
    {
        match ($this->type) {
            'plan' => $this->dispatch('tree:toggle-plan', planId: $this->nodeId),
            'limit' => $this->dispatch('tree:toggle-limit', limitId: $this->nodeId),
            'feature' => $this->dispatch('tree:toggle-feature', featureId: $this->nodeId),
            'entitlement' => $this->dispatch('tree:toggle-entitlement', entitlementId: $this->nodeId),
            default => null,
        };
    }

    public function mount(
        $nodeId = null,
        $label = '',
        $identifier = null,
        $status = 'active',
        $type = 'task',
        $description = '',
        $lastComment = null,
        $meta = [],
        $draggable = false,
        $isActive = false
    ) {
        $this->nodeId = $nodeId;
        $this->label = $label;
        $this->identifier = $identifier;
        $this->status = $status;
        $this->type = $type;
        $this->description = $description;
        $this->lastComment = $lastComment;
        $this->meta = $meta;
        $this->draggable = $draggable;
        $this->isActive = $isActive;
    }
};
@endphp

@php
    $statusColor = match ($status) {
        'active', 'enabled', 'in-progress' => 'bg-emerald-500',
        'completed', 'done' => 'bg-sky-500',
        'draft', 'pending' => 'bg-amber-400',
        'inactive', 'disabled' => 'bg-slate-300',
        'error', 'blocked' => 'bg-rose-500',
        default => 'bg-slate-300',
    };

    $statusTone = match ($status) {
        'active', 'enabled', 'in-progress' => 'bg-emerald-50/70 text-emerald-500 ring-emerald-200/80',
        'completed', 'done' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'draft', 'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'inactive', 'disabled' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'error', 'blocked' => 'bg-rose-50 text-rose-700 ring-rose-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };

    $typeIcon = match ($type) {
        'plan' => 'heroicon-o-credit-card',
        'limit' => 'heroicon-o-adjustments-horizontal',
        'feature' => 'heroicon-o-sparkles',
        'entitlement' => 'heroicon-o-key',
        default => 'heroicon-o-square-3-stack-3d',
    };

    $typeBadgeLabel = match ($type) {
        'plan' => __('Commercial'),
        'limit' => __('Constraint'),
        'feature' => __('Capability'),
        'entitlement' => __('Grant'),
        default => str_replace('_', ' ', ucfirst($type)),
    };
    $typeBadgeClass = match ($type) {
        'plan' => 'border-brand/15 bg-brand/[0.06] text-brand',
        'limit' => 'border-sky-200 bg-sky-50 text-sky-700',
        'feature' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'entitlement' => 'border-amber-200 bg-amber-50 text-amber-700',
        default => 'border-slate-200 bg-slate-50 text-slate-600',
    };
    $readableStatus = str_replace('-', ' ', ucfirst($status));
    $rowTone = $isActive
        ? 'bg-slate-50/70'
        : 'bg-transparent hover:bg-slate-50/70 focus-within:bg-slate-50/70';
@endphp

<li
    wire:key="tree-node-{{ $nodeId }}"
    class="relative list-none group"
>
    <span aria-hidden="true" class="pointer-events-none absolute start-[-1.45rem] top-[1.45rem] h-px w-[1.05rem] rounded-full bg-slate-300/90 sm:start-[-1.95rem] sm:w-[1.45rem]"></span>
    <span aria-hidden="true" class="pointer-events-none absolute start-[-1.5rem] top-[1.3rem] size-2 rounded-full bg-white ring-2 ring-slate-200 sm:start-[-2rem]"></span>
    <article class="overflow-hidden px-3 py-2 transition {{ $rowTone }}">
        <div class="flex items-start gap-3">
            <div class="hidden shrink-0 items-center gap-2 pt-0.5 md:flex">
                @if ($draggable)
                    <span class="drag-handle inline-flex min-h-8 min-w-8 cursor-grab items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-slate-400 active:cursor-grabbing hover:bg-white">
                        <x-heroicon-o-bars-3 class="size-4" />
                    </span>
                @endif

                <span
                    x-show="$store.productTree.showIcons"
                    class="inline-flex min-h-8 min-w-8 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-slate-500"
                >
                    <x-dynamic-component :component="$typeIcon" class="size-4" />
                </span>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="truncate text-sm font-semibold tracking-tight text-slate-900">{{ $label }}</h4>

                    <span
                        x-show="$store.productTree.showStatuses"
                        class="inline-flex items-center gap-1 rounded-sm px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset {{ $statusTone }}"
                    >
                        <span class="size-2 rounded-full {{ $statusColor }}"></span>
                        <span>{{ $readableStatus }}</span>
                    </span>
                </div>

                @if ($meta)
                    <div class="mt-2 hidden flex-wrap items-center gap-1.5 sm:flex">
                        @foreach ($meta as $metaItem)
                            @php
                                $metaTone = data_get($metaItem, 'tone', 'slate');
                                $metaStyle = data_get($metaItem, 'style', 'tag');
                                $metaClass = match ($metaTone) {
                                    'brand' => $metaStyle === 'metric'
                                        ? 'border-brand/15 bg-brand/[0.06] text-brand'
                                        : 'border-brand/10 bg-brand/[0.04] text-brand/90',
                                    'sky' => $metaStyle === 'metric'
                                        ? 'border-sky-200 bg-sky-50 text-sky-700'
                                        : 'border-sky-200 bg-sky-50/70 text-sky-700',
                                    'emerald' => $metaStyle === 'metric'
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                        : 'border-emerald-200 bg-emerald-50/70 text-emerald-700',
                                    'amber' => $metaStyle === 'metric'
                                        ? 'border-amber-200 bg-amber-50 text-amber-700'
                                        : 'border-amber-200 bg-amber-50/70 text-amber-700',
                                    default => $metaStyle === 'metric'
                                        ? 'border-slate-200 bg-slate-100 text-slate-700'
                                        : 'border-slate-200 bg-slate-50 text-slate-600',
                                };
                            @endphp

                            <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-1 text-[11px] font-medium {{ $metaClass }}">
                                <span class="uppercase tracking-[0.14em] opacity-70">{{ data_get($metaItem, 'label') }}</span>
                                <span class="normal-case tracking-normal opacity-100">{{ data_get($metaItem, 'value') }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif

                <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px] font-semibold text-slate-500 sm:mt-2 sm:gap-2 sm:text-xs">
                    @if ($identifier)
                        <span
                            x-show="$store.productTree.showIdentifiers"
                            class="inline-flex items-center rounded-sm border border-slate-200 bg-slate-50 px-1.5 py-0.5 font-mono tracking-tight text-slate-600"
                        >
                            {{ $identifier }}
                        </span>
                    @endif

                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] uppercase tracking-[0.12em] {{ $typeBadgeClass }}">
                        {{ $typeBadgeLabel }}
                    </span>

                    @if ($lastComment)
                        <span class="inline-flex items-center gap-1 rounded-sm bg-blue-50 px-1.5 py-0.5 text-blue-700">
                            <x-heroicon-s-chat-bubble-left-right class="size-3.5" />
                            <span>{{ __('Unread comment') }}</span>
                        </span>
                    @endif
                </div>

                @if ($description)
                    <p class="mt-2 hidden line-clamp-2 text-sm leading-6 text-slate-600 sm:block">
                        {{ $description }}
                    </p>
                @endif
            </div>

            <div class="hidden items-center rounded-xl border border-slate-200/90 bg-slate-50/90 p-1 shadow-xs md:flex md:opacity-0 md:transition-all md:group-hover:opacity-100 md:group-focus-within:opacity-100">
                @if ($type === 'plan')
                    <button type="button" wire:click="$dispatch('tree:open-add-price', { planId: {{ $nodeId }} })" class="inline-flex min-h-8 min-w-8 items-center justify-center rounded-lg text-brand transition hover:bg-white hover:text-brand">
                        <x-heroicon-o-currency-dollar class="size-4" />
                    </button>

                    <span class="h-5 w-px bg-slate-200"></span>
                @endif

                <button type="button" wire:click="requestEdit" class="inline-flex min-h-8 min-w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white hover:text-slate-700">
                    <x-heroicon-o-pencil class="size-4" />
                </button>

                <span class="h-5 w-px bg-slate-200"></span>

                <button type="button" wire:click="requestToggle" class="inline-flex min-h-8 min-w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white hover:text-slate-700">
                    <x-heroicon-o-power class="size-4" />
                </button>

                <span class="h-5 w-px bg-slate-200"></span>

                <button type="button" wire:click="requestDelete" class="inline-flex min-h-8 min-w-8 items-center justify-center rounded-lg text-rose-500 transition hover:bg-white hover:text-rose-700">
                    <x-heroicon-o-trash class="size-4" />
                </button>
            </div>

            <details class="relative md:hidden [&_summary::-webkit-details-marker]:hidden">
                <summary class="inline-flex min-h-8 min-w-8 cursor-pointer list-none items-center justify-center rounded-lg border border-slate-200/90 bg-slate-50/90 text-slate-500 shadow-xs transition hover:bg-white">
                    <x-heroicon-o-ellipsis-horizontal class="size-4.5" />
                </summary>

                <div class="pt-3">
                    <div class="grid grid-cols-{{ $type === 'plan' ? 4 : 3 }} gap-2">
                        @if ($type === 'plan')
                            <button type="button" wire:click="$dispatch('tree:open-add-price', { planId: {{ $nodeId }} })" class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-md border border-brand/20 bg-brand/5 px-2.5 py-2 text-xs font-medium text-brand">
                                <x-heroicon-o-currency-dollar class="size-4" />
                                <span>{{ __('Price') }}</span>
                            </button>
                        @endif

                        <button type="button" wire:click="requestEdit" class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white px-2.5 py-2 text-xs font-medium text-slate-700">
                            <x-heroicon-o-pencil class="size-4" />
                            <span>{{ __('Edit') }}</span>
                        </button>

                        <button type="button" wire:click="requestToggle" class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white px-2.5 py-2 text-xs font-medium text-slate-700">
                            <x-heroicon-o-power class="size-4" />
                            <span>{{ __('Toggle') }}</span>
                        </button>

                        <button type="button" wire:click="requestDelete" class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-md border border-rose-200 bg-white px-2.5 py-2 text-xs font-medium text-rose-600">
                            <x-heroicon-o-trash class="size-4" />
                            <span>{{ __('Delete') }}</span>
                        </button>
                    </div>
                </div>
            </details>
        </div>
    </article>
</li>
