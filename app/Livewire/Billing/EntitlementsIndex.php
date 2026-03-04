<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use App\Models\AccountEntitlement;
use App\Services\OrganizationContext;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Tenant: List and CRUD account_entitlements for current org's account.
 * No account → show "No account attached" empty state.
 */
final class EntitlementsIndex extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $feature_key = '';

    public string $value = '';

    public ?string $expires_at = null;

    public function mount(OrganizationContext $context): mixed
    {
        $organization = $context->current();
        if ($organization === null) {
            return $this->redirect(route('organizations.index'), navigate: true);
        }

        return null;
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->feature_key = '';
        $this->value = '';
        $this->expires_at = '';
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $account = $this->getAccount();
        if ($account === null) {
            return;
        }
        $entitlement = AccountEntitlement::where('account_id', $account->id)->find($id);
        if ($entitlement === null) {
            return;
        }
        $this->editingId = $entitlement->id;
        $this->feature_key = $entitlement->feature_key;
        $this->value = $entitlement->value ?? '';
        $this->expires_at = $entitlement->expires_at?->format('Y-m-d') ?? '';
        $this->showForm = true;
    }

    public function save(OrganizationContext $context): void
    {
        $account = $this->getAccount();
        if ($account === null) {
            return;
        }

        $this->validate([
            'feature_key' => 'required|string|max:255',
            'value' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date',
        ]);

        if ($this->editingId !== null) {
            $entitlement = AccountEntitlement::where('account_id', $account->id)->find($this->editingId);
            if ($entitlement) {
                $entitlement->update([
                    'feature_key' => $this->feature_key,
                    'value' => $this->value ?: null,
                    'expires_at' => $this->expires_at ? $this->expires_at : null,
                ]);
            }
        } else {
            AccountEntitlement::create([
                'account_id' => $account->id,
                'feature_key' => $this->feature_key,
                'value' => $this->value ?: null,
                'expires_at' => $this->expires_at ? $this->expires_at : null,
            ]);
        }

        $this->showForm = false;
        $this->editingId = null;
        $this->reset(['feature_key', 'value', 'expires_at']);
    }

    public function deleteEntitlement(int $id, OrganizationContext $context): void
    {
        $account = $this->getAccount();
        if ($account === null) {
            return;
        }
        $entitlement = AccountEntitlement::where('account_id', $account->id)->find($id);
        if ($entitlement) {
            $entitlement->delete();
        }
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->reset(['feature_key', 'value', 'expires_at']);
    }

    private function getAccount(): ?\App\Models\Account
    {
        $organization = app(OrganizationContext::class)->current();

        return $organization?->account;
    }

    public function render(OrganizationContext $context): View
    {
        $organization = $context->current();
        $account = $organization?->account;
        $entitlements = $account
            ? $account->entitlements()->orderBy('feature_key')->get()
            : collect();

        return view('livewire.billing.entitlements-index', [
            'organization' => $organization,
            'account' => $account,
            'entitlements' => $entitlements,
        ]);
    }
}
