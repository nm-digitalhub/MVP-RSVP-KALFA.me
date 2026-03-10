<?php

declare(strict_types=1);

namespace App\Livewire\System\Products;

use App\Enums\ProductStatus;
use App\Models\AccountProduct;
use App\Models\Product;
use App\Models\ProductPlan;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?ProductStatus $filterStatus = null;

    public ?string $filterCategory = null;

    #[Layout('layouts.app')]
    #[Title('Products')]
    public function render(): View
    {
        $productsQuery = Product::query()
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('name', 'like', $search)
                        ->orWhere('slug', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhereHas('productPlans', function ($q) use ($search) {
                            $q->where('sku', 'like', $search);
                        });
                });
            })
            ->when($this->filterStatus !== null, fn ($query) => $query->where('status', $this->filterStatus))
            ->when($this->filterCategory !== null, fn ($query) => $query->where('category', $this->filterCategory))
            ->withCount([
                'activeEntitlements as active_entitlements_count',
                'activeLimits as active_limits_count',
                'enabledFeatures as enabled_features_count',
                'productPlans as product_plans_count',
                'accountProducts as active_account_products_count' => fn ($query) => $query->active(),
            ])
            ->orderBy('name');

        return view('livewire.system.products.index', [
            'products' => $productsQuery->paginate(12),
            'stats' => [
                'total' => Product::query()->count(),
                'active' => Product::query()->where('status', ProductStatus::Active)->count(),
                'draft' => Product::query()->where('status', ProductStatus::Draft)->count(),
                'plans' => ProductPlan::query()->where('is_active', true)->count(),
                'assignments' => AccountProduct::query()->active()->count(),
            ],
            'categories' => Product::query()
                ->whereNotNull('category')
                ->selectRaw('category, COUNT(*) as aggregate')
                ->groupBy('category')
                ->orderBy('category')
                ->pluck('aggregate', 'category'),
        ]);
    }

    public function openCreateModal(): mixed
    {
        return $this->redirectRoute('system.products.create', navigate: true);
    }

    public function setFilterStatus(?ProductStatus $status): void
    {
        $this->filterStatus = $status;
        $this->resetPage();
    }

    public function setFilterCategory(?string $category): void
    {
        $this->filterCategory = $category;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'filterStatus',
            'filterCategory',
        ]);

        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
}
