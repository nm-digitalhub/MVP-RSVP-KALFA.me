<?php

declare(strict_types=1);

namespace App\Livewire\System\Coupons;

use App\Enums\CouponDiscountType;
use App\Enums\CouponTargetType;
use App\Models\Coupon;
use App\Models\ProductPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Transition;
use Livewire\Component;

final class CreateCouponWizard extends Component
{
    public int $step = 1;

    public int $totalSteps = 5;

    // Step 1 — Basic
    public string $code = '';

    public string $description = '';

    public bool $isActive = true;

    public string $expiresAt = '';

    // Step 2 — Scope
    public string $targetType = 'global';

    /** @var array<int> */
    public array $targetPlanIds = [];

    // Step 3 — Discount
    public string $discountType = 'percentage';

    public string $discountValue = '';

    /** null = forever; positive integer = number of months */
    public string $discountDurationMonths = '';

    // Step 4 — Limits
    public string $maxUses = '';

    public string $maxUsesPerAccount = '';

    public bool $firstTimeOnly = false;

    // -------------------------------------------------------------------

    #[Transition]
    public function nextStep(): mixed
    {
        $this->validateStep($this->step);

        if ($this->step >= $this->totalSteps) {
            return $this->save();
        }

        $this->step++;

        return null;
    }

    #[Transition(type: 'backward')]
    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function updatedCode(string $value): void
    {
        $this->code = strtoupper(trim($value));
    }

    // -------------------------------------------------------------------
    // Per-step validation
    // -------------------------------------------------------------------

    private function validateStep(int $step): void
    {
        $rules = match ($step) {
            1 => $this->step1Rules(),
            2 => $this->step2Rules(),
            3 => $this->step3Rules(),
            4 => $this->step4Rules(),
            default => [],
        };

        if (empty($rules)) {
            return;
        }

        $this->validate($rules);
    }

    /** @return array<string, mixed> */
    private function step1Rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64', 'alpha_dash:ascii', Rule::unique('coupons', 'code')],
            'description' => ['nullable', 'string', 'max:255'],
            'expiresAt' => ['nullable', 'date', 'after:today'],
        ];
    }

    /** @return array<string, mixed> */
    private function step2Rules(): array
    {
        return [
            'targetType' => ['required', Rule::in(array_column(CouponTargetType::cases(), 'value'))],
            'targetPlanIds' => [
                Rule::requiredIf($this->targetType === CouponTargetType::Plan->value),
                'array',
            ],
            'targetPlanIds.*' => ['integer', 'exists:product_plans,id'],
        ];
    }

    /** @return array<string, mixed> */
    private function step3Rules(): array
    {
        $rules = [
            'discountType' => ['required', Rule::in(array_column(CouponDiscountType::cases(), 'value'))],
            'discountValue' => ['required', 'integer', 'min:1'],
            'discountDurationMonths' => ['nullable', 'integer', 'min:1', 'max:120'],
        ];

        if ($this->discountType === CouponDiscountType::Percentage->value) {
            $rules['discountValue'][] = 'max:100';
        }

        return $rules;
    }

    /** @return array<string, mixed> */
    private function step4Rules(): array
    {
        return [
            'maxUses' => ['nullable', 'integer', 'min:1'],
            'maxUsesPerAccount' => ['nullable', 'integer', 'min:1'],
        ];
    }

    // -------------------------------------------------------------------

    private function save(): \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
    {
        $this->validateStep(4); // ensure last step passes

        /** @var \App\Models\User $user */
        $user = Auth::user();

        Coupon::create([
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
            'created_by' => $user->id,
        ]);

        session()->flash('success', "הקופון {$this->code} נוצר בהצלחה.");

        return redirect()->route('system.coupons.index');
    }

    // -------------------------------------------------------------------

    #[Layout('layouts.app')]
    public function render(): View
    {
        $plans = $this->targetType === CouponTargetType::Plan->value
            ? ProductPlan::query()->where('is_active', true)->with('product')->get()
            : collect();

        return view('livewire.system.coupons.create-coupon-wizard', [
            'plans' => $plans,
            'discountTypes' => CouponDiscountType::cases(),
            'targetTypes' => CouponTargetType::cases(),
        ]);
    }
}
