<?php

declare(strict_types=1);

namespace App\Livewire\System\Products;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProductTree extends Component
{
    public Product $product;

    public string $search = '';

    #[Computed]
    public function plans(): Collection
    {
        return $this->product->productPlans()
            ->withCount(['activePrices', 'prices', 'subscriptions'])
            ->with(['prices' => fn ($q) => $q->orderBy('amount')])
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%"))
            ->get();
    }

    #[Computed]
    public function entitlements(): Collection
    {
        return $this->product->productEntitlements()
            ->when($this->search, fn ($q) => $q
                ->where('label', 'like', "%{$this->search}%")
                ->orWhere('feature_key', 'like', "%{$this->search}%"))
            ->orderBy('feature_key')
            ->get();
    }

    #[Computed]
    public function limits(): Collection
    {
        return $this->product->limits()
            ->when($this->search, fn ($q) => $q
                ->where('label', 'like', "%{$this->search}%")
                ->orWhere('limit_key', 'like', "%{$this->search}%"))
            ->orderBy('limit_key')
            ->get();
    }

    #[Computed]
    public function features(): Collection
    {
        return $this->product->features()
            ->when($this->search, fn ($q) => $q
                ->where('label', 'like', "%{$this->search}%")
                ->orWhere('feature_key', 'like', "%{$this->search}%"))
            ->orderBy('feature_key')
            ->get();
    }

    public function reorderPlans(array $ids): void
    {
        foreach ($ids as $index => $id) {
            $this->product->productPlans()
                ->whereKey((int) $id)
                ->update(['sort_order' => $index]);
        }

        unset($this->plans);
    }

    // ── Plan proxies ──────────────────────────────────────────────────────────

    public function requestAddPlan(): void
    {
        $this->dispatch('tree:open-add-plan');
    }

    public function requestEditPlan(int $id): void
    {
        $this->dispatch('tree:open-edit-plan', planId: $id);
    }

    public function requestDeletePlan(int $id): void
    {
        $this->dispatch('tree:delete-plan', planId: $id);
    }

    public function requestAddPriceForPlan(int $id): void
    {
        $this->dispatch('tree:open-add-price', planId: $id);
    }

    public function requestTogglePlan(int $id): void
    {
        $this->dispatch('tree:toggle-plan', planId: $id);
    }

    // ── Limit proxies ─────────────────────────────────────────────────────────

    public function requestAddLimit(): void
    {
        $this->dispatch('tree:open-add-limit');
    }

    public function requestEditLimit(int $id): void
    {
        $this->dispatch('tree:open-edit-limit', limitId: $id);
    }

    public function requestDeleteLimit(int $id): void
    {
        $this->dispatch('tree:delete-limit', limitId: $id);
    }

    public function requestToggleLimit(int $id): void
    {
        $this->dispatch('tree:toggle-limit', limitId: $id);
    }

    // ── Feature proxies ───────────────────────────────────────────────────────

    public function requestAddFeature(): void
    {
        $this->dispatch('tree:open-add-feature');
    }

    public function requestEditFeature(int $id): void
    {
        $this->dispatch('tree:open-edit-feature', featureId: $id);
    }

    public function requestDeleteFeature(int $id): void
    {
        $this->dispatch('tree:delete-feature', featureId: $id);
    }

    public function requestToggleFeature(int $id): void
    {
        $this->dispatch('tree:toggle-feature', featureId: $id);
    }

    public function render(): View
    {
        return view('livewire.system.products.product-tree');
    }
}
