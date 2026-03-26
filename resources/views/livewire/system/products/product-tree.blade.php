<div
    dir="{{ isRTL() ? 'rtl' : 'ltr' }}"
    data-audit-section="product-structure"
    x-data="{
        settingsOpen: false,
        expandAll() { $dispatch('tree-expand-all') },
        collapseAll() { $dispatch('tree-collapse-all') },
    }"
    x-init="
        if (!Alpine.store('productTree')) {
            Alpine.store('productTree', {
                showIcons: true,
                showStatuses: true,
                showIdentifiers: true,
                showComments: false,
            })
        }
    "
>
@php
    $sectionItems = [
        ['label' => __('Plans & Pricing'), 'count' => $this->plans->count(), 'href' => '#tree-plans-pricing'],
        ['label' => __('Limits'), 'count' => $this->limits->count(), 'href' => '#tree-limits'],
        ['label' => __('Features'), 'count' => $this->features->count(), 'href' => '#tree-features'],
        ['label' => __('Entitlements'), 'count' => $this->entitlements->count(), 'href' => '#tree-entitlements'],
    ];
@endphp

<section class="mb-4 rounded-[1.5rem] border border-slate-200 bg-white shadow-sm" data-audit-section="product-structure-toolbar">
    <div class="px-3.5 py-3.5 sm:px-5 sm:py-5">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-2xl">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Structure') }}</p>
                <h3 class="mt-1 text-base font-semibold tracking-tight text-slate-900 sm:text-lg">{{ __('Manage product structure') }}</h3>
            </div>

            <div class="hidden flex-wrap gap-2 xl:max-w-xl xl:justify-end sm:flex">
                @foreach ($sectionItems as $sectionItem)
                    <a href="{{ $sectionItem['href'] }}" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">
                        <span>{{ $sectionItem['label'] }}</span>
                        <span class="text-[11px] text-slate-400">{{ $sectionItem['count'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="mt-3 grid gap-2.5 xl:mt-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
            <div class="relative min-w-0">
                <svg class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
                </svg>

                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search plans, limits, features...') }}"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 ps-10 pe-4 text-sm text-slate-800 transition focus:border-brand focus:bg-white focus:ring-4 focus:ring-brand/10 focus:outline-none"
                />
            </div>

            <div class="flex flex-wrap gap-2 xl:justify-end">
                <div class="hidden items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1 sm:flex">
                    <button
                        type="button"
                        @click="expandAll()"
                        class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                    >
                        <x-heroicon-o-arrows-pointing-out class="size-4" />
                        <span>{{ __('Expand all') }}</span>
                    </button>

                    <button
                        type="button"
                        @click="collapseAll()"
                        class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                    >
                        <x-heroicon-o-arrows-pointing-in class="size-4" />
                        <span>{{ __('Collapse all') }}</span>
                    </button>
                </div>

                <button
                    type="button"
                    @click="settingsOpen = !settingsOpen"
                    :class="settingsOpen ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200'"
                    class="inline-flex min-h-9 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium transition-colors"
                >
                    <x-heroicon-o-adjustments-horizontal class="size-4" />
                    <span class="hidden sm:inline">{{ __('Display') }}</span>
                </button>
            </div>
        </div>
    </div>
</section>

<div x-show="settingsOpen" x-collapse class="mb-5">
    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
        <p class="mb-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">
            {{ __('Display Settings') }}
        </p>

        <div class="flex flex-wrap gap-3">
            <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">
                <input type="checkbox" x-model="$store.productTree.showStatuses" class="size-4 rounded border-slate-300 text-brand" />
                <span>{{ __('Show status badges') }}</span>
            </label>

            <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">
                <input type="checkbox" x-model="$store.productTree.showIdentifiers" class="size-4 rounded border-slate-300 text-brand" />
                <span>{{ __('Show identifiers') }}</span>
            </label>

            <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">
                <input type="checkbox" x-model="$store.productTree.showIcons" class="size-4 rounded border-slate-300 text-brand" />
                <span>{{ __('Show icons') }}</span>
            </label>
        </div>
    </div>
</div>

<div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm" data-audit-section="product-structure-list">
<ul class="divide-y divide-slate-200 text-sm">
    <livewire:tree-branch
        :label="__('Plans & Pricing')"
        :description="__('Commercial plans, included capacity, and pricing structure.')"
        :count="$this->plans->count()"
        icon="heroicon-o-credit-card"
        :default-open="true"
        :add-label="__('Add Plan')"
        add-action="tree:open-add-plan"
        tone="brand"
        :context-label="__('Commercial')"
    >
        @if ($this->plans->isEmpty())
            <li class="list-none">
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                    {{ __('No plans yet.') }}
                </div>
            </li>
        @else
            <li class="list-none">
                <div
                    x-data
                    x-init="
                        Sortable.create($el, {
                            animation: 150,
                            handle: '.drag-handle',
                            onEnd() {
                                $wire.reorderPlans(
                                    [...$el.querySelectorAll('[data-id]')].map(el => el.dataset.id)
                                )
                            }
                        })
                    "
                    class="divide-y divide-slate-200"
                >
                    @foreach ($this->plans as $plan)
                        @php
                            $primaryPrice = $plan->prices->firstWhere('billing_cycle', \App\Enums\ProductPriceBillingCycle::Monthly)
                                ?? $plan->prices->firstWhere('is_active', true)
                                ?? $plan->prices->first();
                            $commercialMetadata = (array) data_get($plan->metadata, 'commercial', []);
                            $includedQuantity = data_get($commercialMetadata, 'included_quantity');
                            $rawIncludedUnit = (string) data_get($commercialMetadata, 'included_unit', '');
                            $unitLabels = [
                                'voice_rsvp_calls' => __('RSVP calls'),
                            ];
                            $includedUnitLabel = $unitLabels[$rawIncludedUnit] ?? str_replace('_', ' ', $rawIncludedUnit);
                            $billingCycleLabel = match ($primaryPrice?->billing_cycle?->value) {
                                'yearly' => __('Yearly'),
                                'usage' => __('Usage'),
                                default => __('Monthly'),
                            };
                            $planMeta = array_values(array_filter([
                                $primaryPrice
                                    ? [
                                        'label' => __('Price'),
                                        'value' => number_format($primaryPrice->amount / 100, 0).' '.$primaryPrice->currency.' / '.$billingCycleLabel,
                                        'tone' => 'brand',
                                        'style' => 'metric',
                                    ]
                                    : null,
                                $includedQuantity && $rawIncludedUnit !== ''
                                    ? [
                                        'label' => __('Included'),
                                        'value' => number_format((int) $includedQuantity).' '.$includedUnitLabel,
                                        'tone' => 'brand',
                                    ]
                                    : null,
                                [
                                    'label' => __('Subscriptions'),
                                    'value' => number_format((int) $plan->subscriptions_count),
                                    'tone' => 'slate',
                                ],
                            ]));
                        @endphp
                        <div data-id="{{ $plan->id }}" wire:key="tree-plan-{{ $plan->id }}">
                            <livewire:tree-node
                                :nodeId="$plan->id"
                                :label="$plan->name"
                                :identifier="$plan->slug"
                                :status="$plan->is_active ? 'active' : 'inactive'"
                               type="plan"
                                :description="$plan->description"
                                :meta="$planMeta"
                                :draggable="true"
                                :is-active="$plan->is_active"
                            />
                        </div>
                    @endforeach
                </div>
            </li>
        @endif
    </livewire:tree-branch>

    <livewire:tree-branch
        :label="__('Limits')"
        :description="__('Operational caps and quantitative thresholds enforced by the product.')"
        :count="$this->limits->count()"
        icon="heroicon-o-adjustments-horizontal"
        :default-open="true"
        :add-label="__('Add Limit')"
        add-action="tree:open-add-limit"
        tone="sky"
        :context-label="__('Constraint')"
    >
        @forelse ($this->limits as $limit)
            @php
                $limitMeta = [[
                    'label' => __('Threshold'),
                    'value' => $limit->value !== null && $limit->value !== '' ? (string) $limit->value : __('Not set'),
                    'tone' => 'sky',
                    'style' => 'metric',
                ]];
            @endphp
            <livewire:tree-node
                wire:key="tree-limit-{{ $limit->id }}"
                :nodeId="$limit->id"
                :label="$limit->label ?: $limit->limit_key"
                :identifier="$limit->limit_key"
                :status="$limit->is_active ? 'active' : 'inactive'"
                :type="'limit'"
                :description="$limit->description"
                :meta="$limitMeta"
                :is-active="$limit->is_active"
            />
        @empty
            <li class="list-none">
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                    {{ __('No limits yet.') }}
                </div>
            </li>
        @endforelse
    </livewire:tree-branch>

    <livewire:tree-branch
        :label="__('Features')"
        :description="__('Feature flags and capabilities that can be enabled or disabled.')"
        :count="$this->features->count()"
        icon="heroicon-o-sparkles"
        :default-open="true"
        :add-label="__('Add Feature')"
        add-action="tree:open-add-feature"
        tone="emerald"
        :context-label="__('Capability')"
    >
        @forelse ($this->features as $feature)
            @php
                $featureMeta = [[
                    'label' => __('Mode'),
                    'value' => filled($feature->value) ? (string) $feature->value : ($feature->is_enabled ? __('Enabled') : __('Disabled')),
                    'tone' => 'emerald',
                    'style' => 'metric',
                ]];
            @endphp
            <livewire:tree-node
                wire:key="tree-feature-{{ $feature->id }}"
                :nodeId="$feature->id"
                :label="$feature->label ?: $feature->feature_key"
                :identifier="$feature->feature_key"
                :status="$feature->is_enabled ? 'enabled' : 'disabled'"
                :type="'feature'"
                :description="$feature->description"
                :meta="$featureMeta"
                :is-active="$feature->is_enabled"
            />
        @empty
            <li class="list-none">
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                    {{ __('No features yet.') }}
                </div>
            </li>
        @endforelse
    </livewire:tree-branch>

    <livewire:tree-branch
        :label="__('Entitlements')"
        :description="__('Default grants that propagate into account runtime state.')"
        :count="$this->entitlements->count()"
        icon="heroicon-o-key"
        :default-open="false"
        tone="amber"
        :context-label="__('Grant')"
    >
        @forelse ($this->entitlements as $entitlement)
            @php
                $constraintCount = collect((array) $entitlement->constraints)
                    ->filter(fn ($value) => filled($value))
                    ->count();
                $entitlementMeta = array_values(array_filter([
                    [
                        'label' => $entitlement->type?->label() ?? __('Value'),
                        'value' => filled($entitlement->value) ? (string) $entitlement->value : __('Granted'),
                        'tone' => 'amber',
                        'style' => 'metric',
                    ],
                    $constraintCount > 0
                        ? [
                            'label' => __('Constraints'),
                            'value' => number_format($constraintCount),
                            'tone' => 'amber',
                        ]
                        : null,
                ]));
            @endphp
            <livewire:tree-node
                wire:key="tree-entitlement-{{ $entitlement->id }}"
                :nodeId="$entitlement->id"
                :label="$entitlement->label ?: $entitlement->feature_key"
                :identifier="$entitlement->feature_key"
                :status="$entitlement->is_active ? 'active' : 'inactive'"
                :type="'entitlement'"
                :description="$entitlement->description"
                :meta="$entitlementMeta"
                :is-active="$entitlement->is_active"
            />
        @empty
            <li class="list-none">
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                    {{ __('No entitlements yet.') }}
                </div>
            </li>
        @endforelse
    </livewire:tree-branch>
</ul>
</div>
</div>
