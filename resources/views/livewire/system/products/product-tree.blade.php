<div
    dir="{{ isRTL() ? 'rtl' : 'ltr' }}"
    x-data="{
        settingsOpen: false,
        expandAll()  { $dispatch('tree-expand-all') },
        collapseAll(){ $dispatch('tree-collapse-all') },
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

{{-- ── Toolbar ─────────────────────────────────────────────────────────── --}}
<div class="mb-4 flex flex-wrap items-center gap-2">

    {{-- Search --}}
    <div class="relative min-w-0 flex-1">
        <svg class="absolute start-3 top-1/2 size-4 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
        </svg>

        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('Search plans, limits, features...') }}"
            class="w-full rounded-xl border border-transparent bg-slate-100 py-2 ps-9 pe-4 text-sm font-semibold text-slate-800 placeholder-slate-400 transition focus:border-brand focus:bg-white focus:ring-4 focus:ring-brand/10 focus:outline-none"
        />
    </div>

    {{-- Expand / Collapse --}}
    <div class="flex shrink-0 items-center gap-1 rounded-xl border border-slate-200 bg-white p-1">

        <button
            type="button"
            @click="expandAll()"
            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors"
        >
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/>
            </svg>
            <span class="hidden sm:inline">{{ __('Expand') }}</span>
        </button>

        <div class="h-4 w-px bg-slate-200"></div>

        <button
            type="button"
            @click="collapseAll()"
            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors"
        >
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h8"/>
            </svg>
            <span class="hidden sm:inline">{{ __('Collapse') }}</span>
        </button>

    </div>

    {{-- Settings --}}
    <button
        type="button"
        @click="settingsOpen = !settingsOpen"
        :class="settingsOpen ? 'bg-brand text-white border-brand' : 'bg-white text-slate-600 border-slate-200'"
        class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-bold transition-colors"
    >
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0"/>
        </svg>
        {{ __('View') }}
    </button>

</div>


{{-- ── Settings Panel ───────────────────────────────────────────────────── --}}
<div x-show="settingsOpen" x-collapse class="mb-4">

    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">

        <p class="mb-3 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
            {{ __('Display Settings') }}
        </p>

        <div class="flex flex-wrap gap-3">

            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-white px-3 py-2 shadow-sm">
                <input type="checkbox" x-model="$store.productTree.showStatuses" class="size-4 rounded border-slate-300 text-brand"/>
                <span class="text-xs font-bold text-slate-700">{{ __('Status indicators') }}</span>
            </label>

            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-white px-3 py-2 shadow-sm">
                <input type="checkbox" x-model="$store.productTree.showIdentifiers" class="size-4 rounded border-slate-300 text-brand"/>
                <span class="text-xs font-bold text-slate-700">{{ __('Identifiers') }}</span>
            </label>

            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-white px-3 py-2 shadow-sm">
                <input type="checkbox" x-model="$store.productTree.showIcons" class="size-4 rounded border-slate-300 text-brand"/>
                <span class="text-xs font-bold text-slate-700">{{ __('Icons') }}</span>
            </label>

        </div>

    </div>
</div>


{{-- ── Tree Container ───────────────────────────────────────────────────── --}}
<div class="rounded-2xl border border-slate-200/70 bg-white px-3 py-3 shadow-sm">

<ul class="space-y-1 text-sm">

{{-- ── Plans ───────────────────────────────────────── --}}
<livewire:tree-branch 
    :label="__('Plans & Pricing')"
    :count="$this->plans->count()"
    :default-open="true"
    :add-label="__('Add Plan')"
    add-action="requestAddPlan"
>

@if ($this->plans->isEmpty())
<div class="py-1 px-2 text-xs text-slate-400 italic">
{{ __('No plans yet.') }}
</div>
@else

<div
x-data
x-init="
Sortable.create($el,{
animation:150,
handle:'.drag-handle',
onEnd(){
$wire.reorderPlans(
[...$el.querySelectorAll('[data-id]')]
.map(el => el.dataset.id)
)
}
})
"
>

@foreach ($this->plans as $plan)

<div data-id="{{ $plan->id }}" wire:key="tree-plan-{{ $plan->id }}">

<x-tree.node
:node-id="$plan->id"
:label="$plan->name"
:identifier="$plan->slug"
:status="$plan->is_active ? 'active' : 'inactive'"
:draggable="true"
:edit-action="'requestEditPlan(' . $plan->id . ')'"
:delete-action="'requestDeletePlan(' . $plan->id . ')'"
:toggle-action="'requestTogglePlan(' . $plan->id . ')'"
/>

</div>

@endforeach

</div>

@endif

</livewire:tree-branch>


{{-- ── Limits ───────────────────────────────────────── --}}
<livewire:tree-branch 
:label="__('Limits')"
:count="$this->limits->count()"
:default-open="true"
:add-label="__('Add Limit')"
add-action="requestAddLimit"
>

@foreach ($this->limits as $limit)

<x-tree.node
wire:key="tree-limit-{{ $limit->id }}"
:node-id="$limit->id"
:label="$limit->label ?: $limit->limit_key"
:identifier="$limit->limit_key"
:status="$limit->is_active ? 'active' : 'inactive'"
:edit-action="'requestEditLimit(' . $limit->id . ')'"
:delete-action="'requestDeleteLimit(' . $limit->id . ')'"
:toggle-action="'requestToggleLimit(' . $limit->id . ')'"
/>

@endforeach

</livewire:tree-branch>


{{-- ── Features ───────────────────────────────────── --}}
<livewire:tree-branch
:label="__('Features')"
:count="$this->features->count()"
:default-open="true"
:add-label="__('Add Feature')"
add-action="requestAddFeature"
>

@foreach ($this->features as $feature)

<x-tree.node
wire:key="tree-feature-{{ $feature->id }}"
:node-id="$feature->id"
:label="$feature->label ?: $feature->feature_key"
:identifier="$feature->feature_key"
:status="$feature->is_enabled ? 'enabled' : 'disabled'"
:edit-action="'requestEditFeature(' . $feature->id . ')'"
:delete-action="'requestDeleteFeature(' . $feature->id . ')'"
:toggle-action="'requestToggleFeature(' . $feature->id . ')'"
/>

@endforeach

</livewire:tree-branch>


{{-- ── Entitlements ───────────────────────────────── --}}
<livewire:tree-branch
:label="__('Entitlements')"
:count="$this->entitlements->count()"
:default-open="false"
>

@foreach ($this->entitlements as $entitlement)

<x-tree.node
wire:key="tree-entitlement-{{ $entitlement->id }}"
:node-id="$entitlement->id"
:label="$entitlement->label ?: $entitlement->feature_key"
:identifier="$entitlement->feature_key"
:status="$entitlement->is_active ? 'active' : 'inactive'"
/>

@endforeach

</livewire:tree-branch>


</ul>
</div>

</div>