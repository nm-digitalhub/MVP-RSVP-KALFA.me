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

    public function render(): View
    {
        return view('livewire.system.products.product-tree');
    }
}
