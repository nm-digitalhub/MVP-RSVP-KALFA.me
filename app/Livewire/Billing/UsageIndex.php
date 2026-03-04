<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use App\Services\OrganizationContext;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Tenant: Read-only list of account_feature_usage for current org's account.
 */
final class UsageIndex extends Component
{
    public string $filter_feature_key = '';

    public string $filter_period_key = '';

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
            return view('livewire.billing.usage-index', [
                'organization' => $organization,
                'account' => null,
                'usage' => collect(),
            ]);
        }

        $query = $account->featureUsage()->orderByDesc('period_key')->orderBy('feature_key');

        if ($this->filter_feature_key !== '') {
            $query->where('feature_key', 'like', '%'.$this->filter_feature_key.'%');
        }
        if ($this->filter_period_key !== '') {
            $query->where('period_key', 'like', '%'.$this->filter_period_key.'%');
        }

        $usage = $query->get();

        return view('livewire.billing.usage-index', [
            'organization' => $organization,
            'account' => $account,
            'usage' => $usage,
        ]);
    }
}
