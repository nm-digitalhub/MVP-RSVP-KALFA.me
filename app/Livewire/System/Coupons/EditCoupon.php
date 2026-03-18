<?php

declare(strict_types=1);

namespace App\Livewire\System\Coupons;

use App\Enums\CouponDiscountType;
use App\Enums\CouponTargetType;
use App\Models\Coupon;
use App\Models\ProductPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

final class EditCoupon extends Component
{
    public Coupon $coupon;

    public string $code = '';

    public string $description = '';

    public bool $isActive = true;

    public string $expiresAt = '';

    public string $targetType = 'global';

    /** @var array<int> */
    public array $targetPlanIds = [];

    public string $discountType = 'percentage';

    public string $discountValue = '';

    /** null = forever; positive integer = number of months */
    public string $discountDurationMonths = '';

    public string $maxUses = '';

    public string $maxUsesPerAccount = '';

    public bool $firstTimeOnly = false;

    public function mount(Coupon $coupon): void
    {
        $this->coupon = $coupon;
        $this->code = $coupon->code;
        $this->description = $coupon->description ?? '';
        $this->isActive = $coupon->is_active;
        $this->expiresAt = $coupon->expires_at?->format('Y-m-d') ?? '';
        $this->targetType = $coupon->target_type->value;
        $this->targetPlanIds = $coupon->target_ids ?? [];
        $this->discountType = $coupon->discount_type->value;
        $this->discountValue = (string) $coupon->discount_value;
        $this->discountDurationMonths = $coupon->discount_duration_months !== null ? (string) $coupon->discount_duration_months : '';
        $this->maxUses = $coupon->max_uses !== null ? (string) $coupon->max_uses : '';
        $this->maxUsesPerAccount = $coupon->max_uses_per_account !== null ? (string) $coupon->max_uses_per_account : '';
        $this->firstTimeOnly = $coupon->first_time_only;
    }

    public function updatedCode(string $value): void
    {
        $this->code = strtoupper(trim($value));
    }

    public function save(): \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
    {
        $rules = [
            'code' => ['required', 'string', 'max:64', 'alpha_dash:ascii',
                Rule::unique('coupons', 'code')->ignore($this->coupon->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'expiresAt' => ['nullable', 'date', 'after:today'],
            'targetType' => ['required', Rule::in(array_column(CouponTargetType::cases(), 'value'))],
            'targetPlanIds' => [
                Rule::requiredIf($this->targetType === CouponTargetType::Plan->value),
                'array',
            ],
            'targetPlanIds.*' => ['integer', 'exists:product_plans,id'],
            'discountType' => ['required', Rule::in(array_column(CouponDiscountType::cases(), 'value'))],
            'discountValue' => ['required', 'integer', 'min:1'],
            'discountDurationMonths' => ['nullable', 'integer', 'min:1', 'max:120'],
            'maxUses' => ['nullable', 'integer', 'min:1'],
            'maxUsesPerAccount' => ['nullable', 'integer', 'min:1'],
        ];

        if ($this->discountType === CouponDiscountType::Percentage->value) {
            $rules['discountValue'][] = 'max:100';
        }

        $this->validate($rules);

        $this->coupon->update([
            'code' => strtoupper($this->code),
            'description' => $this->description ?: null,
            'discount_type' => $this->discountType,
            'discount_value' => (int) $this->discountValue,
            'discount_duration_months' => ($this->discountType === CouponDiscountType::Percentage->value && $this->discountDurationMonths !== '')
                ? (int) $this->discountDurationMonths
                : null,
            'target_type' => $this->targetType,
            'target_ids' => $this->targetType === CouponTargetType::Plan->value
                ? $this->targetPlanIds
                : null,
            'max_uses' => $this->maxUses !== '' ? (int) $this->maxUses : null,
            'max_uses_per_account' => $this->maxUsesPerAccount !== '' ? (int) $this->maxUsesPerAccount : null,
            'first_time_only' => $this->firstTimeOnly,
            'is_active' => $this->isActive,
            'expires_at' => $this->expiresAt !== '' ? $this->expiresAt : null,
        ]);

        session()->flash('success', "הקופון {$this->code} עודכן בהצלחה.");

        return redirect()->route('system.coupons.index');
    }

    #[Layout('layouts.app')]
    #[Title('עריכת קופון')]
    public function render(): View
    {
        $plans = $this->targetType === CouponTargetType::Plan->value
            ? ProductPlan::query()->where('is_active', true)->with('product')->get()
            : collect();

        return view('livewire.system.coupons.edit-coupon', [
            'plans' => $plans,
            'discountTypes' => CouponDiscountType::cases(),
            'targetTypes' => CouponTargetType::cases(),
        ]);
    }
}
