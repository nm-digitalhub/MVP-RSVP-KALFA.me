<?php

declare(strict_types=1);

namespace App\Livewire\System\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Transition;
use Livewire\Component;

final class CreateProductModal extends Component
{
    public bool $isOpen = false;

    public int $step = 1;

    public string $name = '';

    public string $slug = '';

    public ?string $description = '';

    public ?string $category = '';

    #[On('open-create-product-modal')]
    public function open(): void
    {
        $this->resetForm();
        $this->isOpen = true;
        $this->step = 1;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', 'string', 'max:100'],
        ];
    }

    #[Transition(skip: true)]
    public function close(): void
    {
        $this->isOpen = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'name',
            'slug',
            'description',
            'category',
        ]);
    }

    #[Transition(type: 'forward')]
    public function goToStep(int $step): void
    {
        if ($step === 2) {
            $this->validateOnly('name');
            $this->validateOnly('slug');
        }

        $this->step = $step;
    }

    #[Transition(skip: true)]
    public function createProduct(): void
    {
        $validated = $this->validate();

        $product = Product::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'description' => $validated['description'],
            'category' => $validated['category'],
            'status' => ProductStatus::Draft,
        ]);

        $this->close();

        session()->flash('success', __('Product created successfully.'));

        $this->dispatch('product-created', productId: $product->id);
    }

    public function updatedName(): void
    {
        if (blank($this->slug)) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function generateSlug(): void
    {
        $this->slug = Str::slug($this->name);
    }

    public function render(): View
    {
        return view('livewire.system.products.partials.create-product-modal');
    }
}
