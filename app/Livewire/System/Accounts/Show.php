<?php

declare(strict_types=1);

namespace App\Livewire\System\Accounts;

use App\Models\Account;
use App\Models\Organization;
use App\Services\SystemAuditLogger;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * System admin: Account detail with Overview, Organizations, Entitlements, Usage, Billing Intents.
 * Attach/detach Organization → Account (explicit actions only; no billing logic change).
 */
final class Show extends Component
{
    public Account $account;

    public string $activeTab = 'overview';

    /** For attach: organization id to attach to this account */
    public ?int $attach_organization_id = null;

    public bool $showEditForm = false;

    public string $edit_name = '';

    public ?int $edit_owner_user_id = null;

    public ?int $edit_sumit_customer_id = null;

    // Entitlement management
    public bool $showEntitlementForm = false;

    public ?int $editingEntitlementId = null;

    public string $entitlement_feature_key = '';

    public string $entitlement_value = '';

    public ?string $entitlement_expires_at = null;

    #[Layout('layouts.app')]
    #[Title('Account Details')]
    public function mount(Account $account): void
    {
        $this->account = $account;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->cancelEntitlement();
    }

    // --- Account Management ---

    public function openEdit(): void
    {
        $this->edit_name = $this->account->name ?? '';
        $this->edit_owner_user_id = $this->account->owner_user_id;
        $this->edit_sumit_customer_id = $this->account->sumit_customer_id;
        $this->showEditForm = true;
    }

    public function saveAccount(): void
    {
        $this->validate([
            'edit_name' => 'nullable|string|max:255',
            'edit_owner_user_id' => 'nullable|exists:users,id',
            'edit_sumit_customer_id' => 'nullable|integer|min:0',
        ]);

        $this->account->update([
            'name' => $this->edit_name ?: null,
            'owner_user_id' => $this->edit_owner_user_id,
            'sumit_customer_id' => $this->edit_sumit_customer_id,
        ]);

        SystemAuditLogger::log(auth()->user(), 'account.updated', $this->account, [
            'name' => $this->edit_name,
            'owner_id' => $this->edit_owner_user_id,
            'sumit_id' => $this->edit_sumit_customer_id,
        ]);

        $this->account->refresh();
        $this->showEditForm = false;
        session()->flash('success', __('Account updated successfully.'));
    }

    public function cancelEdit(): void
    {
        $this->showEditForm = false;
    }

    // --- Entitlement Management ---

    public function openCreateEntitlement(): void
    {
        $this->resetEntitlementFields();
        $this->showEntitlementForm = true;
    }

    public function openEditEntitlement(int $id): void
    {
        $e = $this->account->entitlements()->findOrFail($id);
        $this->editingEntitlementId = $id;
        $this->entitlement_feature_key = $e->feature_key;
        $this->entitlement_value = (string) $e->value;
        $this->entitlement_expires_at = $e->expires_at?->format('Y-m-d');
        $this->showEntitlementForm = true;
    }

    public function saveEntitlement(): void
    {
        $this->validate([
            'entitlement_feature_key' => 'required|string|max:100',
            'entitlement_value' => 'nullable|string|max:255',
            'entitlement_expires_at' => 'nullable|date',
        ]);

        $data = [
            'feature_key' => $this->entitlement_feature_key,
            'value' => $this->entitlement_value,
            'expires_at' => $this->entitlement_expires_at,
        ];

        if ($this->editingEntitlementId) {
            $e = $this->account->entitlements()->findOrFail($this->editingEntitlementId);
            $e->update($data);
            $action = 'account.entitlement_updated';
        } else {
            $this->account->entitlements()->create($data);
            $action = 'account.entitlement_created';
        }

        SystemAuditLogger::log(auth()->user(), $action, $this->account, $data);

        $this->cancelEntitlement();
        session()->flash('success', __('Entitlement saved.'));
    }

    public function deleteEntitlement(int $id): void
    {
        $e = $this->account->entitlements()->findOrFail($id);
        $key = $e->feature_key;
        $e->delete();

        SystemAuditLogger::log(auth()->user(), 'account.entitlement_deleted', $this->account, ['feature_key' => $key]);
        session()->flash('success', __('Entitlement deleted.'));
    }

    public function cancelEntitlement(): void
    {
        $this->showEntitlementForm = false;
        $this->resetEntitlementFields();
    }

    protected function resetEntitlementFields(): void
    {
        $this->editingEntitlementId = null;
        $this->entitlement_feature_key = '';
        $this->entitlement_value = '';
        $this->entitlement_expires_at = null;
    }

    // --- Organization Linking ---

    public function attachOrganization(): void
    {
        $org = Organization::find($this->attach_organization_id);
        if ($org === null || $org->account_id === $this->account->id) {
            $this->attach_organization_id = null;

            return;
        }
        $org->update(['account_id' => $this->account->id]);
        SystemAuditLogger::log(
            auth()->user(),
            'account.organization_attached',
            $this->account,
            ['organization_id' => $org->id, 'organization_name' => $org->name],
        );
        $this->account->refresh();
        $this->attach_organization_id = null;
    }

    public function detachOrganization(int $organizationId): void
    {
        $org = Organization::where('account_id', $this->account->id)->find($organizationId);
        if ($org === null) {
            return;
        }
        $org->update(['account_id' => null]);
        SystemAuditLogger::log(
            auth()->user(),
            'account.organization_detached',
            $this->account,
            ['organization_id' => $org->id, 'organization_name' => $org->name],
        );
        $this->account->refresh();
    }

    public function render(): View
    {
        $organizationsAttached = $this->account->organizations()->orderBy('name')->get();
        $organizationsAvailable = Organization::where(function ($q) {
            $q->whereNull('account_id')->orWhere('account_id', '!=', $this->account->id);
        })->orderBy('name')->get();

        $entitlements = $this->account->entitlements()->orderBy('feature_key')->get();
        $usage = $this->account->featureUsage()->orderByDesc('period_key')->orderBy('feature_key')->get();
        $billingIntents = $this->account->billingIntents()->orderByDesc('created_at')->get();

        return view('livewire.system.accounts.show', [
            'organizationsAttached' => $organizationsAttached,
            'organizationsAvailable' => $organizationsAvailable,
            'entitlements' => $entitlements,
            'usage' => $usage,
            'billingIntents' => $billingIntents,
        ]);
    }
}
