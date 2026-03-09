<?php

declare(strict_types=1);

namespace App\Livewire\System\Products;

use App\Enums\EntitlementType;
use App\Models\ProductEntitlement;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class EntitlementRow extends Component
{
    public ProductEntitlement $entitlement;

    public bool $isEditing = false;

    public string $editLabel = '';

    public string $editValue = '';

    public string $editDescription = '';

    public function mount(ProductEntitlement $entitlement): void
    {
        $this->entitlement = $entitlement;
    }

    public function startEdit(): void
    {
        $this->isEditing = true;
        $this->editLabel = $this->entitlement->label ?? $this->entitlement->feature_key;
        $this->editValue = $this->entitlement->value ?? '';
        $this->editDescription = $this->entitlement->description ?? '';
    }

    public function cancelEdit(): void
    {
        $this->isEditing = false;
        $this->reset(['editLabel', 'editValue', 'editDescription']);
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editLabel' => 'required|string|max:255',
            'editValue' => 'required|string|max:255',
            'editDescription' => 'nullable|string|max:1000',
        ]);

        $this->entitlement->update([
            'label' => $this->editLabel,
            'value' => $this->editValue,
            'description' => $this->editDescription,
        ]);

        $this->isEditing = false;
        $this->reset(['editLabel', 'editValue', 'editDescription']);

        session()->flash('success', __('Entitlement updated.'));
    }

    public function toggleActive(): void
    {
        $this->entitlement->update([
            'is_active' => ! $this->entitlement->is_active,
        ]);

        $message = $this->entitlement->is_active ? __('Entitlement activated.') : __('Entitlement deactivated.');
        session()->flash('success', $message);
    }

    public function delete(): void
    {
        $this->entitlement->delete();
        session()->flash('success', __('Entitlement removed.'));
    }

    public function getTypeIcon(): string
    {
        $type = $this->entitlement->type ?? EntitlementType::Text;

        return match ($type) {
            EntitlementType::Boolean => 'o-switch',
            EntitlementType::Number => 'o-chart-bar',
            EntitlementType::Text => 'o-document-text',
            EntitlementType::Enum => 'o-chevron-selector-vertical',
        };
    }

    public function getTypeColor(): string
    {
        $type = $this->entitlement->type ?? EntitlementType::Text;

        return match ($type) {
            EntitlementType::Boolean => 'indigo',
            EntitlementType::Number => 'emerald',
            EntitlementType::Text => 'amber',
            EntitlementType::Enum => 'purple',
        };
    }

    public function render(): View
    {
        return view('livewire.system.products.partials.entitlement-row', [
            'typeIcon' => $this->getTypeIcon(),
            'typeToneClasses' => $this->getTypeToneClasses(),
            'valueToneClasses' => $this->getValueToneClasses(),
        ]);
    }

    public function getTypeToneClasses(): string
    {
        return match ($this->getTypeColor()) {
            'indigo' => 'bg-indigo-50 text-indigo-600',
            'emerald' => 'bg-emerald-50 text-emerald-600',
            'amber' => 'bg-amber-50 text-amber-600',
            'purple' => 'bg-purple-50 text-purple-600',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public function getValueToneClasses(): string
    {
        return match ($this->getTypeColor()) {
            'indigo' => 'bg-indigo-50 text-indigo-700',
            'emerald' => 'bg-emerald-50 text-emerald-700',
            'amber' => 'bg-amber-50 text-amber-700',
            'purple' => 'bg-purple-50 text-purple-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }
}
