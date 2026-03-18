<?php

declare(strict_types=1);

namespace App\Livewire\System\Coupons;

use App\Models\Coupon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterActive = '';

    public string $filterType = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterActive(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $couponId): void
    {
        $coupon = Coupon::findOrFail($couponId);
        $coupon->update(['is_active' => ! $coupon->is_active]);
    }

    public function deleteCoupon(int $couponId): void
    {
        Coupon::findOrFail($couponId)->delete();
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    #[Title('ניהול קופונים')]
    public function render(): View
    {
        $coupons = Coupon::query()
            ->with('creator')
            ->withCount('redemptions')
            ->when($this->search, fn (Builder $q) => $q->where(function (Builder $inner) {
                $inner->where('code', 'ilike', "%{$this->search}%")
                    ->orWhere('description', 'ilike', "%{$this->search}%");
            }))
            ->when($this->filterActive === '1', fn (Builder $q) => $q->where('is_active', true)
                ->where(fn (Builder $i) => $i->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->when($this->filterActive === '0', fn (Builder $q) => $q->where('is_active', false))
            ->when($this->filterType, fn (Builder $q) => $q->where('discount_type', $this->filterType))
            ->latest()
            ->paginate(20);

        return view('livewire.system.coupons.index', compact('coupons'));
    }
}
