<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use App\Services\OrganizationContext;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Tenant: Read-only list of billing_intents for current org's account.
 */
final class BillingIntentsIndex extends Component
{
    public function mount(OrganizationContext $context): mixed
    {
        $organization = $context->current();
        if ($organization === null) {
            return $this->redirect(route('organizations.index'), navigate: true);
        }

        return null;
    }

    public function render(OrganizationContext $context): View
    {
        $organization = $context->current();
        $account = $organization?->account;

        if ($account === null) {
            return view('livewire.billing.billing-intents-index', [
                'organization' => $organization,
                'account' => null,
                'intents' => collect(),
            ]);
        }

        $intents = $account->billingIntents()->orderByDesc('created_at')->get();

        return view('livewire.billing.billing-intents-index', [
            'organization' => $organization,
            'account' => $account,
            'intents' => $intents,
        ]);
    }
}
