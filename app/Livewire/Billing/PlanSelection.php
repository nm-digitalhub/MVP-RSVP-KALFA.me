<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use App\Models\ProductPlan;
use App\Services\OrganizationContext;
use App\Services\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Choose a Plan')]
final class PlanSelection extends Component
{
    public bool $showConfirmTrial = false;

    public ?int $selectedPlanId = null;

    public function mount(OrganizationContext $context): mixed
    {
        $organization = $context->current();

        if ($organization === null) {
            return $this->redirect(route('organizations.index'), navigate: true);
        }

        // If account already has billing access, go straight to dashboard.
        if ($organization->account?->hasBillingAccess()) {
            return $this->redirect(route('dashboard'), navigate: true);
        }

        // Check if organization already has an active subscription
        $hasActiveSubscription = $organization->account?->activeSubscriptions()->exists() ?? false;

        if ($hasActiveSubscription) {
            return $this->redirect(route('dashboard'), navigate: true);
        }

        return null;
    }

    public function confirmTrial(int $planId): void
    {
        $this->selectedPlanId = $planId;
        $this->showConfirmTrial = true;
    }

    public function startTrial(OrganizationContext $context, SubscriptionService $subscriptionService): mixed
    {
        $organization = $context->current();

        if ($organization === null || $this->selectedPlanId === null) {
            return null;
        }

        $this->authorize('update', $organization);

        $plan = ProductPlan::where('is_active', true)->findOrFail($this->selectedPlanId);

        $subscriptionService->startTrial(
            account: $organization->account,
            plan: $plan,
            trialEndsAt: now()->addDays(14),
        );

        $organization->account->invalidateBillingAccessCache();

        session()->flash('success', __('Your 14-day free trial has started. Enjoy full access!'));

        return $this->redirect(route('dashboard'), navigate: true);
    }

    public function purchase(int $planId, OrganizationContext $context): mixed
    {
        $organization = $context->current();

        if ($organization === null) {
            return null;
        }

        $this->authorize('update', $organization);

        $plan = ProductPlan::where('is_active', true)->findOrFail($planId);

        return $this->redirect(route('billing.checkout', ['plan' => $plan->id]), navigate: false);
    }

    public function cancelConfirm(): void
    {
        $this->showConfirmTrial = false;
        $this->selectedPlanId = null;
    }

    public function render(OrganizationContext $context): View
    {
        /** @var Collection<int, ProductPlan> $plans */
        $plans = ProductPlan::with(['product', 'activePrices'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('livewire.billing.plan-selection', [
            'plans' => $plans,
            'organization' => $context->current(),
        ]);
    }
}
