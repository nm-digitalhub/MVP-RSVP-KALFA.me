<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use App\Models\Account;
use App\Services\OrganizationContext;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Tenant: Account overview for current organization.
 * If org has no account, show "Create account" (explicit action only).
 */
final class AccountOverview extends Component
{
    public function mount(OrganizationContext $context): mixed
    {
        $organization = $context->current();
        if ($organization === null) {
            return $this->redirect(route('organizations.index'), navigate: true);
        }

        return null;
    }

    public function createAccount(OrganizationContext $context): mixed
    {
        $organization = $context->current();
        if ($organization === null) {
            return $this->redirect(route('organizations.index'), navigate: true);
        }

        $this->authorize('update', $organization);

        if ($organization->account_id !== null) {
            return null;
        }

        $owner = $organization->owner();
        $account = Account::create([
            'type' => 'organization',
            'name' => $organization->name,
            'owner_user_id' => $owner?->id,
        ]);

        $organization->update(['account_id' => $account->id]);

        return $this->redirect(route('billing.account'), navigate: true);
    }

    public function render(OrganizationContext $context): View
    {
        $organization = $context->current();
        $account = $organization?->account;

        return view('livewire.billing.account-overview', [
            'organization' => $organization,
            'account' => $account,
        ]);
    }
}
