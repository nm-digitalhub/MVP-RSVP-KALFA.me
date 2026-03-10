@props(['entitlement', 'typeIcon', 'typeToneClasses', 'valueToneClasses', 'isEditing', 'editLabel', 'editValue', 'editDescription'])

<div class="bg-white rounded-2xl border {{ $entitlement->is_active ? 'border-slate-200' : 'border-slate-100 opacity-60' }} shadow-sm hover:shadow-md transition-all">
    @if(!$isEditing)
        <div class="p-4 sm:p-5">
            <div class="flex items-start gap-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $typeToneClasses }}">
                   <x-dynamic-component :component="$typeIcon" class="size-5" />        
                </div>

                <div class="flex-1 min-w-0">
                    <div class="mb-2 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h4 class="text-sm sm:text-base font-black text-slate-900 mb-1">
                                {{ $entitlement->label ?? $entitlement->feature_key }}
                            </h4>
                            <code class="text-[10px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded uppercase tracking-wider break-all inline-block">
                                {{ $entitlement->feature_key }}
                            </code>
                        </div>

                        <div class="flex w-full items-center justify-end gap-1 sm:w-auto sm:shrink-0">
                            <button wire:click="toggleActive" wire:loading.attr="disabled" class="min-h-[36px] min-w-[36px] flex items-center justify-center rounded-lg {{ $entitlement->is_active ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-400' }} hover:{{ $entitlement->is_active ? 'bg-emerald-200' : 'bg-slate-200' }} transition-all cursor-pointer" title="{{ $entitlement->is_active ? __('Deactivate') : __('Activate') }}">
                                @if($entitlement->is_active)
                                    <x-fwb-o-eye-slash class="size-4" />
                                @else
                                    <x-fwb-o-eye class="size-4" />
                                @endif
                            </button>
                            <button wire:click="startEdit" class="min-h-[36px] min-w-[36px] flex items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-all cursor-pointer" title="{{ __('Edit') }}">
                                <x-heroicon-o-pencil class="size-4" />
                            </button>
                            <button wire:click="delete" wire:confirm="{{ __('Delete this entitlement?') }}" class="min-h-[36px] min-w-[36px] flex items-center justify-center rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all cursor-pointer" title="{{ __('Delete') }}">
                                <x-heroicon-o-trash class="size-4" />
                            </button>
                        </div>
                    </div>

                    @if($entitlement->isBoolean())
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-{{ $entitlement->value ? 'check-circle' : 'x-circle' }} class="size-4 {{ $entitlement->value ? 'text-emerald-500' : 'text-rose-500' }}" />
                            <span class="text-xs sm:text-sm font-medium {{ $entitlement->value ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $entitlement->value ? __('Enabled') : __('Disabled') }}
                            </span>
                        </div>
                    @else
                        <div class="inline-flex max-w-full items-center break-all rounded-xl px-3 py-1.5 text-xs font-bold sm:text-sm {{ $valueToneClasses }}">
                            {{ $entitlement->value ?: 'Not set' }}
                        </div>
                    @endif

                    @if($entitlement->description)
                        <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                            {{ $entitlement->description }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @else
        <form wire:submit.prevent="saveEdit" class="p-4 sm:p-5 bg-slate-50 rounded-2xl border border-slate-200">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase px-1 block">{{ __('Label') }}</label>
                    <input wire:model="editLabel" type="text" class="block w-full px-4 py-3 rounded-xl bg-white border border-transparent focus:bg-white focus:ring-4 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold" />
                    @error('editLabel') <p class="mt-1 text-xs text-rose-500 font-bold">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase px-1 block">{{ __('Value') }}</label>
                    <input wire:model="editValue" type="text" class="block w-full px-4 py-3 rounded-xl bg-white border border-transparent focus:bg-white focus:ring-4 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold" />
                    @error('editValue') <p class="mt-1 text-xs text-rose-500 font-bold">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="space-y-2 mb-4">
                <label class="text-[10px] font-black text-slate-400 uppercase px-1 block">{{ __('Description') }}</label>
                <textarea wire:model="editDescription" rows="2" class="block w-full px-4 py-3 rounded-xl bg-white border border-transparent focus:bg-white focus:ring-4 focus:ring-brand/10 focus:border-brand transition-all text-sm font-bold resize-none"></textarea>
                @error('editDescription') <p class="mt-1 text-xs text-rose-500 font-bold">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button type="submit" wire:loading.attr="disabled" class="min-h-[44px] flex-1 py-3 bg-brand text-white font-black rounded-xl hover:bg-brand-hover transition-all cursor-pointer">
                    {{ __('Save') }}
                </button>
                <button type="button" wire:click="cancelEdit" class="min-h-[44px] px-6 py-3 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all cursor-pointer">
                    {{ __('Cancel') }}
                </button>
            </div>
        </form>
    @endif
</div>
