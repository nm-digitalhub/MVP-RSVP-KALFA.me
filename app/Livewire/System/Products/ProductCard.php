<?php

declare(strict_types=1);

namespace App\Livewire\System\Products;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ProductCard extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    public function render(): View
    {
        return view('livewire.system.products.partials.product-card');
    }
}
