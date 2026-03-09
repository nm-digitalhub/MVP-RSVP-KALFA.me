<?php

declare(strict_types=1);

namespace App\Livewire\System\Products;

use App\Enums\ProductStatus;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ProductStatusBadge extends Component
{
    public ProductStatus $status;

    public function mount(ProductStatus $status): void
    {
        $this->status = $status;
    }

    public function getColorClasses(): string
    {
        return match ($this->status) {
            ProductStatus::Draft => 'bg-slate-100 text-slate-700 border-slate-200',
            ProductStatus::Active => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            ProductStatus::Archived => 'bg-rose-100 text-rose-700 border-rose-200',
        };
    }

    public function getIcon(): string
    {
        return match ($this->status) {
            ProductStatus::Draft => 'o-document',
            ProductStatus::Active => 'o-check-circle',
            ProductStatus::Archived => 'o-archive-box',
        };
    }

    public function render(): View
    {
        return view('livewire.system.products.partials.product-status-badge', [
            'colorClasses' => $this->getColorClasses(),
            'icon' => $this->getIcon(),
        ]);
    }
}
