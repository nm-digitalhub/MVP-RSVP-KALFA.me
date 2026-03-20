@php

use Livewire\Component;

new class extends Component
{
    // פונקציה זו תופעל כאשר המשתמש ילחץ על "Save changes" בפופ-אפ ההגדרות.
    // תוכל בעתיד להוסיף כאן לוגיקה ששומרת את העדפות המשתמש למסד הנתונים.
    public function savePreferences()
    {
        // לדוגמה: auth()->user()->update(['tree_preferences' => ...]);
        
        // כרגע רק נרענן/נסיים כדי להראות פעולת שרת אופציונלית
    }
}
@endphp

<div class="mb-6 flex flex-wrap items-center gap-2" x-data="{ settingsOpen: false }">
    
    {{-- Expand All Button --}}
    {{-- שולח אירוע גלובלי (dispatch) שכל ענף (branch) מאזין לו ופותח את עצמו --}}
    <button 
        type="button" 
        @click="$dispatch('tree-expand-all')"
        class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition"
    >
        <x-heroicon-o-chevron-double-down class="size-4 text-slate-400 dark:text-slate-500" />
        Expand all
    </button>

    {{-- Collapse All Button --}}
    {{-- שולח אירוע גלובלי שסוגר את כל הענפים --}}
    <button 
        type="button" 
        @click="$dispatch('tree-collapse-all')"
        class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition"
    >
        <x-heroicon-o-chevron-double-up class="size-4 text-slate-400 dark:text-slate-500" />
        Collapse all
    </button>

    {{-- View Settings Button & Popover --}}
    <div class="relative">
        <button 
            type="button" 
            @click="settingsOpen = !settingsOpen"
            @click.outside="settingsOpen = false"
            :class="settingsOpen ? 'bg-slate-100 dark:bg-slate-700' : 'bg-white dark:bg-slate-800'"
            class="flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700 transition"
        >
            <x-heroicon-o-adjustments-horizontal class="size-4 text-slate-400 dark:text-slate-500" />
            View settings
        </button>

        {{-- Popover Dialog --}}
        <div 
            x-show="settingsOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            style="display: none;"
            class="absolute top-full right-0 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-4 shadow-xl z-50 dark:border-slate-700 dark:bg-slate-900"
        >
            {{-- Header --}}
            <div class="mb-4 flex items-start justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">View settings</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Adjust the elements displayed in the tree view, like icons, statuses, and comments.
                    </p>
                </div>
                <button @click="settingsOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 transition-colors">
                    <x-heroicon-o-x-mark class="size-4" />
                </button>
            </div>

            {{-- Toggles --}}
            <div class="space-y-3 border-t border-slate-100 dark:border-slate-800 pt-4">
                {{-- Show Icons --}}
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox" x-model="$store.productTree.showIcons" class="size-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:checked:bg-white dark:checked:text-slate-900 transition">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">Show icons</span>
                </label>

                {{-- Show Statuses --}}
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox" x-model="$store.productTree.showStatuses" class="size-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:checked:bg-white dark:checked:text-slate-900 transition">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">Show statuses</span>
                </label>

                {{-- Show Identifiers --}}
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox" x-model="$store.productTree.showIdentifiers" class="size-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:checked:bg-white dark:checked:text-slate-900 transition">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">Show identifiers</span>
                </label>
            </div>

            {{-- Comments Section --}}
            <div class="mt-4 border-t border-slate-100 dark:border-slate-800 pt-4">
                <label class="flex items-center gap-3 cursor-pointer mb-2 group">
                    <input type="checkbox" x-model="$store.productTree.showComments" class="size-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:checked:bg-white dark:checked:text-slate-900 transition">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">Show comments</span>
                </label>

                {{-- Sub-options for comments (enabled only when showComments is true) --}}
                <div class="ml-7 flex flex-col gap-2 transition-opacity duration-200" :class="!$store.productTree.showComments ? 'opacity-40 pointer-events-none' : ''">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" x-model="$store.productTree.commentType" value="all" class="size-3.5 border-slate-300 text-slate-600 focus:ring-slate-600 dark:border-slate-600 dark:bg-slate-800 transition">
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-400">All comments</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" x-model="$store.productTree.commentType" value="unread" class="size-3.5 border-slate-300 text-slate-600 focus:ring-slate-600 dark:border-slate-600 dark:bg-slate-800 transition">
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Unread comments</span>
                    </label>
                </div>
            </div>

            {{-- Footer Actions --}}
            <div class="mt-6 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800 pt-4">
                <button 
                    type="button" 
                    @click="settingsOpen = false" 
                    class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition"
                >
                    Cancel
                </button>
                <button 
                    type="button" 
                    @click="settingsOpen = false"
                    wire:click="savePreferences" 
                    class="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 transition"
                >
                    Save changes
                </button>
            </div>
        </div>
    </div>
</div>