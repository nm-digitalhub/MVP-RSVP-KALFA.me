<?php

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

    // ── Action Methods (Self-contained UI actions) ─────────────
public function requestEdit(): void
{
    match($this->type) {

        'plan' =>
            $this->dispatch('tree:open-edit-plan', planId: $this->nodeId),

        'limit' =>
            $this->dispatch('tree:open-edit-limit', limitId: $this->nodeId),

        'feature' =>
            $this->dispatch('tree:open-edit-feature', featureId: $this->nodeId),

        'entitlement' =>
            $this->dispatch('tree:open-edit-entitlement', entitlementId: $this->nodeId),
    };
}

public function requestDelete(): void
{
    match($this->type) {

        'plan' =>
            $this->dispatch('tree:delete-plan', planId: $this->nodeId),

        'limit' =>
            $this->dispatch('tree:delete-limit', limitId: $this->nodeId),

        'feature' =>
            $this->dispatch('tree:delete-feature', featureId: $this->nodeId),

        'entitlement' =>
            $this->dispatch('tree:delete-entitlement', entitlementId: $this->nodeId),
    };
}

public function requestToggle(): void
{
    match($this->type) {

        'plan' =>
            $this->dispatch('tree:toggle-plan', planId: $this->nodeId),

        'limit' =>
            $this->dispatch('tree:toggle-limit', limitId: $this->nodeId),

        'feature' =>
            $this->dispatch('tree:toggle-feature', featureId: $this->nodeId),

        'entitlement' =>
            $this->dispatch('tree:toggle-entitlement', entitlementId: $this->nodeId),
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
}
?>

@php
    $statusColor = match($status) {
        'active', 'in-progress' => 'bg-sky-400',
        'completed', 'done'     => 'bg-emerald-500',
        'draft', 'pending'      => 'bg-amber-400',
        'error', 'blocked'      => 'bg-rose-500',
        default                 => 'bg-slate-300',
    };

    $typeIcon = match($type) {
        'task'        => 'heroicon-o-check-circle',
        'requirement'  => 'heroicon-o-document-text',
        'doc'         => 'heroicon-o-book-open',
        'folder'      => 'heroicon-o-folder',
        default       => 'heroicon-o-stop',
    };
@endphp

<li
    wire:key="tree-node-{{ $nodeId }}"
    x-data="{
        popoverOpen: false,
        actionsOpen: false,
        updatePopover() {
            const { computePosition, flip, shift, offset } = window.FloatingUI;
            computePosition(this.$refs.trigger, this.$refs.popover, {
                placement: 'right-start',
                middleware: [offset(12), flip(), shift({padding: 8})]
            }).then(({x, y}) => {
                Object.assign(this.$refs.popover.style, {
                    left: x + 'px',
                    top: y + 'px'
                });
            });
        }
    }"
    class="relative list-none"
>
    {{-- Row Container --}}
    <div
        x-ref="trigger"
        @mouseenter="actionsOpen = true; if($store.productTree.showComments) { popoverOpen = true; updatePopover(); }"
        @mouseleave="actionsOpen = false; popoverOpen = false"
        class="group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-all duration-200
               {{ $isActive ? 'bg-blue-600 text-white shadow-md' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300' }}"
    >

        {{-- Left: Icons & Indicators --}}
        <div class="flex items-center gap-2 shrink-0">
            {{-- Comment Indicator --}}
            @if($lastComment)
                <x-heroicon-s-chat-bubble-left-right class="size-3.5 text-slate-400 dark:text-slate-500" />
            @endif

            {{-- Status Dot --}}
            <span x-show="$store.productTree.showStatuses">
                <span class="block size-2.5 rounded-full border-2 border-white dark:border-slate-900 {{ $statusColor }}"></span>
            </span>

            {{-- Type Icon --}}
            <span x-show="$store.productTree.showIcons">
                <x-dynamic-component :component="$typeIcon" class="size-4 opacity-70" />
            </span>
        </div>

        {{-- Center: Identifier & Label --}}
        <div class="flex items-center gap-2 flex-1 min-w-0">
            @if($identifier)
                <span x-show="$store.productTree.showIdentifiers"
                      class="font-mono text-[11px] font-medium tracking-tight opacity-40 {{ $isActive ? 'text-white' : 'text-slate-900 dark:text-slate-100' }}">
                    {{ $identifier }}
                </span>
            @endif
            <span class="truncate font-medium">{{ $label }}</span>
        </div>

        {{-- Right: Actions (Visible on Hover) --}}
        <div x-show="actionsOpen" x-transition.opacity class="flex items-center gap-1 shrink-0">
            <button type="button" wire:click="requestEdit" class="p-1 hover:bg-black/10 dark:hover:bg-white/10 rounded transition">
                <x-heroicon-o-pencil class="size-4" />
            </button>

            <button type="button" wire:click="requestToggle" class="p-1 hover:bg-black/10 dark:hover:bg-white/10 rounded transition">
                <x-heroicon-o-power class="size-4" />
            </button>

            <button type="button" wire:click="requestDelete" class="p-1 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 rounded transition">
                <x-heroicon-o-trash class="size-4" />
            </button>
        </div>
    </div>

    {{-- Rich Popover --}}
    <div
        x-ref="popover"
        x-show="popoverOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-x-2"
        x-transition:enter-end="opacity-100 translate-x-0"
        style="position:fixed; z-index:100; width:280px;"
        class="pointer-events-none overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-2xl"
    >
        {{-- Popover Header --}}
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 px-4 py-3">
            <div class="flex flex-col">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Identifier</span>
                <span class="font-mono text-sm font-semibold dark:text-white">{{ $identifier ?? 'N/A' }}</span>
            </div>
            <div class="flex flex-col items-end">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Status</span>
                <div class="flex items-center gap-1.5">
                    <span class="size-2 rounded-full {{ $statusColor }}"></span>
                    <span class="text-xs font-medium dark:text-slate-200 capitalize">{{ str_replace('-', ' ', $status) }}</span>
                </div>
            </div>
        </div>

        {{-- Popover Content --}}
        <div class="px-4 py-3">
            <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Type</h4>
            <p class="text-sm font-medium dark:text-slate-200 capitalize mb-3">{{ $type }}</p>

            <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Description</h4>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed italic">
                {{ $description ?: 'No description provided for this item.' }}
            </p>
        </div>

        {{-- Popover Footer (Unread Comment) --}}
        @if($lastComment)
        <div class="bg-slate-50 dark:bg-slate-800/80 p-4 border-t border-slate-100 dark:border-slate-700">
            <h4 class="text-[10px] font-bold uppercase tracking-wider text-blue-500 mb-2">Unread comment</h4>
            <div class="flex gap-3">
                <img src="{{ $lastComment['avatar'] ?? 'https://ui-avatars.com/api/?name='.urlencode($lastComment['user_name']) }}"
                     class="size-8 rounded-full border border-white dark:border-slate-700 shadow-sm" alt="" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-0.5">
                        <span class="text-xs font-bold dark:text-slate-200">{{ $lastComment['user_name'] }}</span>
                        <span class="text-[10px] text-slate-400">{{ $lastComment['time'] }}</span>
                    </div>
                    <p class="text-[11px] text-slate-600 dark:text-slate-400 line-clamp-2 leading-snug">
                        {{ $lastComment['text'] }}
                    </p>
                </div>
            </div>
        </div>
        @endif
    </div>
</li>
