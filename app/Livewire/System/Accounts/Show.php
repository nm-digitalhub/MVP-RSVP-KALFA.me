<?php

declare(strict_types=1);

namespace App\Livewire\System\Accounts;

use App\Models\Account;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Services\Sumit\OfficeGuyCustomerSearchService;
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

    public string $sumit_customer_search = '';

    /** @var list<array<string, mixed>> */
    public array $sumit_customer_results = [];

    public ?string $sumit_customer_search_message = null;

    // Entitlement management
    public bool $showEntitlementForm = false;

    public ?int $editingEntitlementId = null;

    public string $entitlement_feature_key = '';

    public string $entitlement_value = '';

    public ?string $entitlement_expires_at = null;

    public ?int $selected_product_id = null;

    public ?int $selected_plan_id = null;

    #[Layout('layouts.app')]
    #[Title('Account Details')]
    protected OfficeGuyCustomerSearchService $customerSearch;

    public function boot(OfficeGuyCustomerSearchService $customerSearch): void
    {
        $this->customerSearch = $customerSearch;
    }

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

    public function searchSumitCustomers(): void
    {
        $this->resetErrorBag('sumit_customer_search');
        $this->sumit_customer_search_message = null;

        $searchTerm = trim($this->sumit_customer_search);

        if ($searchTerm === '') {
            $this->sumit_customer_results = [];
            $this->addError('sumit_customer_search', __('Enter an email address or SUMIT customer ID.'));

            return;
        }

        $results = $this->customerSearch->search($searchTerm);

        $this->sumit_customer_results = $results;

        if ($results === []) {
            $this->sumit_customer_search_message = __('No matching SUMIT customers were found.');
        }
    }

    public function useOwnerEmailForSumitSearch(): void
    {
        $ownerEmail = $this->account->owner?->email;

        if ($ownerEmail === null || $ownerEmail === '') {
            $this->resetErrorBag('sumit_customer_search');
            $this->addError('sumit_customer_search', __('This account owner does not have an email address.'));

            return;
        }

        $this->sumit_customer_search = $ownerEmail;
        $this->searchSumitCustomers();
    }

    public function connectSumitCustomer(int $sumitCustomerId): void
    {
        $candidate = collect($this->sumit_customer_results)
            ->firstWhere('sumit_customer_id', $sumitCustomerId);

        if ($candidate === null) {
            $this->resetErrorBag('sumit_customer_search');
            $this->addError('sumit_customer_search', __('The selected SUMIT customer is no longer available. Search again and retry.'));

            return;
        }

        $previousSumitCustomerId = $this->account->sumit_customer_id;

        $this->account->update([
            'sumit_customer_id' => $sumitCustomerId,
        ]);

        SystemAuditLogger::log(auth()->user(), 'account.sumit_customer_connected', $this->account, [
            'previous_sumit_customer_id' => $previousSumitCustomerId,
            'sumit_customer_id' => $sumitCustomerId,
            'customer_name' => $candidate['name'] ?? null,
            'customer_email' => $candidate['email'] ?? null,
            'source' => $candidate['source'] ?? null,
            'customer_model_class' => $candidate['model_class'] ?? null,
            'customer_model_id' => $candidate['model_id'] ?? null,
        ]);

        $this->account->refresh();
        $this->edit_sumit_customer_id = $this->account->sumit_customer_id;
        $this->sumit_customer_results = [];
        $this->sumit_customer_search_message = null;

        session()->flash('success', __('SUMIT customer connected successfully.'));
    }

    public function disconnectSumitCustomer(): void
    {
        $previousSumitCustomerId = $this->account->sumit_customer_id;

        if ($previousSumitCustomerId === null) {
            return;
        }

        $this->account->update([
            'sumit_customer_id' => null,
        ]);

        SystemAuditLogger::log(auth()->user(), 'account.sumit_customer_disconnected', $this->account, [
            'previous_sumit_customer_id' => $previousSumitCustomerId,
        ]);

        $this->account->refresh();
        $this->edit_sumit_customer_id = null;
        $this->sumit_customer_search_message = null;

        session()->flash('success', __('SUMIT customer disconnected successfully.'));
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

    public function grantSelectedProduct(): void
    {
        if (! $this->selected_product_id) {
            return;
        }

        $this->resetErrorBag(['selected_product_id', 'selected_plan_id']);

        $product = Product::query()
            ->with([
                'productEntitlements',
                'productPlans' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with(['activePrices' => fn ($priceQuery) => $priceQuery->orderBy('id')])
                    ->orderBy('name'),
            ])
            ->find($this->selected_product_id);

        if ($product === null) {
            $this->addError('selected_product_id', __('The selected product is no longer available.'));

            return;
        }

        $commercialPlans = $product->productPlans;

        if ($commercialPlans->isNotEmpty()) {
            if ($this->selected_plan_id === null) {
                $this->addError('selected_plan_id', __('Select a commercial plan before activating this product.'));

                return;
            }

            /** @var ProductPlan|null $plan */
            $plan = $commercialPlans->firstWhere('id', $this->selected_plan_id);

            if ($plan === null) {
                $this->addError('selected_plan_id', __('The selected plan does not belong to this product.'));

                return;
            }

            $subscription = $this->account->subscribeToPlan($plan, metadata: [
                'source' => 'system_account_admin',
                'initiated_from' => 'account_show',
                'granted_by' => auth()->id(),
            ]);

            try {
                $subscription = $subscription->activate(auth()->id());
            } catch (\RuntimeException $exception) {
                $subscription->delete();
                $this->handleCommercialActivationFailure($exception);

                return;
            }

            SystemAuditLogger::log(auth()->user(), 'account.product_subscription_activated', $this->account, [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_plan_id' => $plan->id,
                'product_plan_name' => $plan->name,
                'account_subscription_id' => $subscription->id,
            ]);

            $this->selected_product_id = null;
            $this->selected_plan_id = null;
            session()->flash('success', __('Commercial subscription activated successfully.'));

            return;
        }

        $this->account->grantProduct($product, auth()->id(), metadata: [
            'source' => 'system_account_admin',
            'grant_type' => 'complimentary',
            'initiated_from' => 'account_show',
        ]);

        SystemAuditLogger::log(auth()->user(), 'account.product_granted', $this->account, [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'grant_type' => 'complimentary',
        ]);

        $this->selected_product_id = null;
        $this->selected_plan_id = null;
        session()->flash('success', __('Complimentary product access granted successfully.'));
    }

    public function updatedSelectedProductId(): void
    {
        $this->selected_plan_id = null;
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

    protected function handleCommercialActivationFailure(\RuntimeException $exception): void
    {
        $message = $exception->getMessage();

        if ($message === 'SUMIT subscription requires a default OfficeGuy payment token for the account.') {
            $this->addError('selected_plan_id', __('A default SUMIT payment method is required before activating this subscription.'));

            return;
        }

        if (str_contains($message, 'must have an owner email before syncing to SUMIT')) {
            $this->addError('selected_plan_id', __('An account owner email is required before activating a SUMIT subscription.'));

            return;
        }

        throw $exception;
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
        $paymentMethods = $this->account->paymentMethods()->orderByDesc('is_default')->orderByDesc('id')->get();
        $products = Product::query()
            ->with([
                'productPlans' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with(['activePrices' => fn ($priceQuery) => $priceQuery->orderBy('id')])
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();
        $selectedProduct = $products->firstWhere('id', $this->selected_product_id);
        $selectedProductPlans = $selectedProduct?->productPlans ?? collect();
        $sumitCustomerModelLabel = $this->customerSearch->customerModelLabel();
        $sumitPaymentsReady = filled(config('officeguy.company_id'))
            && filled(config('officeguy.public_key'))
            && filled(config('officeguy.private_key'));

        return view('livewire.system.accounts.show', [
            'organizationsAttached' => $organizationsAttached,
            'organizationsAvailable' => $organizationsAvailable,
            'entitlements' => $entitlements,
            'usage' => $usage,
            'billingIntents' => $billingIntents,
            'paymentMethods' => $paymentMethods,
            'products' => $products,
            'selectedProduct' => $selectedProduct,
            'selectedProductPlans' => $selectedProductPlans,
            'sumitCustomerModelLabel' => $sumitCustomerModelLabel,
            'sumitPaymentsReady' => $sumitPaymentsReady,
        ]);
    }
}
