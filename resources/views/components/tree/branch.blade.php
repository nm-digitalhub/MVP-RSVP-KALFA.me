<?php

use Livewire\Component;

new class extends Component
{
    public string $label = '';
    public int $count = 0;
    public ?string $icon = null;
    public bool $defaultOpen = true;
    public ?string $addLabel = null;
    public ?string $addAction = null;
    
    // אם נרצה לנהל את הילדים מתוך ה-Component עצמו במקום Slot
    public array $children = []; 

    public function mount($label, $count = 0, $icon = null, $defaultOpen = true, $addLabel = null, $addAction = null, $children = [])
    {
        $this->label = $label;
        $this->count = $count;
        $this->icon = $icon;
        $this->defaultOpen = $defaultOpen;
        $this->addLabel = $addLabel;
        $this->addAction = $addAction;
        $this->children = $children;
    }

    // כאן תוכל להוסיף פונקציות Livewire עתידיות, למשל:
    // public function loadMore() { ... }
};
?>

<li
    x-data="{ open: {{ $defaultOpen ? 'true' : 'false' }} }"
    @tree-expand-all.window="open = true"
    @tree-collapse-all.window="open = false"
    class="select-none list-none"
>
    {{-- Branch Header (The clickable row) --}}
    <div
        @click="open = !open"
        class="group flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start transition-colors hover:bg-slate-100 dark:hover:bg-slate-800/50 cursor-pointer"
        :aria-expanded="open"
    >
        {{-- Expand/Collapse Chevron --}}
        <div class="flex items-center justify-center size-5 shrink-0 rounded hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
            <x-heroicon-o-chevron-right
                class="size-3.5 text-slate-400 dark:text-slate-500 transition-transform duration-200"
                x-bind:class="{ 'rotate-90': open }"
            />
        </div>

        {{-- Optional Icon (e.g., Folder or Briefcase) --}}
        @if ($icon)
            <span
                x-show="$store.productTree.showIcons"
                class="text-slate-400 dark:text-slate-500 flex items-center justify-center"
            >
                <x-dynamic-component :component="$icon" class="size-4" />
            </span>
        @endif

        {{-- Label --}}
        <span class="flex-1 text-sm font-medium text-slate-700 dark:text-slate-200">
            {{ $label }}
        </span>

        {{-- Count Badge --}}
        @if ($count > 0)
            <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                {{ $count }}
            </span>
        @endif

        {{-- Add Action Button (Visible on Hover) --}}
        @if ($addAction && $addLabel)
            <button
                type="button"
                wire:click.stop="{{ $addAction }}"
                class="flex items-center gap-1 opacity-0 group-hover:opacity-100 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-all px-2"
            >
                <x-heroicon-o-plus class="size-3.5" />
                <span>{{ $addLabel }}</span>
            </button>
        @endif
    </div>

    {{-- Children Container (The nested tree) --}}
    <div
        x-show="open"
        x-collapse
        class="relative mt-1"
    >
        {{-- Vertical Guide Line (קו העזר האנכי שיוצר את ההיררכיה) --}}
        <div class="absolute top-0 bottom-0 left-[22px] w-px bg-slate-200 dark:bg-slate-700/60"></div>

        <ul class="relative pl-9 space-y-0.5 pb-1">
            {{-- אם השתמשת ב-Slot במקור, אפשר להשאיר את {{ $slot }} --}}
            {{-- אבל מאחר וזה Livewire Component, נהוג לעבור על מערך הילדים: --}}
            
            @if(count($children) > 0)
                @foreach($children as $child)
                    @if(isset($child['children']) && count($child['children']) > 0)
                        {{-- רקורסיה: קריאה לענף נוסף --}}
                        <livewire:tree-branch 
                            :key="'branch-'.$child['id']" 
                            :label="$child['label']" 
                            :count="$child['count'] ?? 0"
                            :icon="$child['icon'] ?? 'heroicon-o-folder'"
                            :children="$child['children']"
                        />
                    @else
                        {{-- קריאה לשורת פריט (Node) --}}
                        <x-tree.node 
                            :nodeId="$child['id']"
                            :label="$child['label']"
                            :identifier="$child['identifier'] ?? null"
                            :status="$child['status'] ?? 'active'"
                            :type="$child['type'] ?? 'task'"
                        />
                    @endif
                @endforeach
            @else
                {{-- במקרה שאתה עדיין מעדיף להזריק תוכן מבחוץ --}}
                {{ $slot ?? '' }}
            @endif
        </ul>
    </div>
</li>