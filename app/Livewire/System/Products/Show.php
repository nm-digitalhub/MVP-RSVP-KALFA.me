<?php

declare(strict_types=1);

namespace App\Livewire\System\Products;

use App\Enums\EntitlementType;
use App\Enums\ProductPriceBillingCycle;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductEntitlement;
use App\Models\ProductFeature;
use App\Models\ProductLimit;
use App\Models\ProductPlan;
use App\Models\ProductPrice;
use App\Models\UsageRecord;
use App\Services\ProductIntegrityChecker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

final class Show extends Component
{
    public Product $product;

    public bool $showEditForm = false;

    public string $name = '';

    public string $slug = '';

    public ?string $description = '';

    public ?string $category = '';

    public ProductStatus $editStatus = ProductStatus::Draft;

    // Entitlement creation
    public bool $showAddEntitlementForm = false;

    public ?int $editingEntitlementId = null;

    public string $newFeatureKey = '';

    public string $newLabel = '';

    public string $newValue = '';

    public EntitlementType $newType = EntitlementType::Text;

    public ?string $newDescription = '';

    public bool $entitlementIsActive = true;

    public ?EntitlementType $filterType = null;

    public bool $showAddLimitForm = false;

    public ?int $editingLimitId = null;

    public string $limitKey = '';

    public string $limitLabel = '';

    public string $limitValue = '';

    public ?string $limitDescription = '';

    public bool $limitIsActive = true;

    public bool $showAddFeatureForm = false;

    public ?int $editingFeatureId = null;

    public string $featureKey = '';

    public string $featureLabel = '';

    public string $featureValue = '';

    public ?string $featureDescription = '';

    public bool $featureIsEnabled = true;

    public bool $showAddPlanForm = false;

    public ?int $editingPlanId = null;

    public string $planName = '';

    public string $planSlug = '';

    public string $planSku = '';

    public ?string $planDescription = '';

    public bool $planIsActive = true;

    public string $planVoiceRsvpLimit = '';

    public string $planVoiceMinutesLimit = '';

    public string $planIncludedUnit = '';

    public string $planIncludedQuantity = '';

    public string $planOverageMetricKey = '';

    public string $planOverageUnit = '';

    public string $planOverageAmountMinor = '';

    public string $planTargetMarginPercent = '';

    public bool $showPriceForm = false;

    public ?int $editingPriceId = null;

    public ?int $pricePlanId = null;

    public string $priceCurrency = 'USD';

    public string $priceAmount = '';

    public ProductPriceBillingCycle $priceBillingCycle = ProductPriceBillingCycle::Monthly;

    public bool $priceIsActive = true;

    protected function productRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug,'.$this->product->id,
            'description' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
        ];
    }

    protected function entitlementRules(): array
    {
        return [
            'newFeatureKey' => 'required|string|max:100',
            'newLabel' => 'required|string|max:255',
            'newValue' => 'nullable|string|max:255',
            'newDescription' => 'nullable|string|max:1000',
        ];
    }

    protected function limitRules(): array
    {
        return [
            'limitKey' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_limits', 'limit_key')
                    ->ignore($this->editingLimitId)
                    ->where(fn ($query) => $query->where('product_id', $this->product->id)),
            ],
            'limitLabel' => ['required', 'string', 'max:255'],
            'limitValue' => ['required', 'string', 'max:255'],
            'limitDescription' => ['nullable', 'string', 'max:1000'],
            'limitIsActive' => ['required', 'boolean'],
        ];
    }

    protected function featureRules(): array
    {
        return [
            'featureKey' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_features', 'feature_key')
                    ->ignore($this->editingFeatureId)
                    ->where(fn ($query) => $query->where('product_id', $this->product->id)),
            ],
            'featureLabel' => ['required', 'string', 'max:255'],
            'featureValue' => ['nullable', 'string', 'max:255'],
            'featureDescription' => ['nullable', 'string', 'max:1000'],
            'featureIsEnabled' => ['required', 'boolean'],
        ];
    }

    protected function planRules(): array
    {
        return [
            'planName' => ['required', 'string', 'max:255'],
            'planSlug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_plans', 'slug')
                    ->ignore($this->editingPlanId)
                    ->where(fn ($query) => $query->where('product_id', $this->product->id)),
            ],
            'planSku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_plans', 'sku')->ignore($this->editingPlanId),
            ],
            'planDescription' => ['nullable', 'string', 'max:1000'],
            'planIsActive' => ['required', 'boolean'],
            'planVoiceRsvpLimit' => ['nullable', 'integer', 'min:0'],
            'planVoiceMinutesLimit' => ['nullable', 'integer', 'min:0'],
            'planIncludedUnit' => ['nullable', 'string', 'max:255'],
            'planIncludedQuantity' => ['nullable', 'integer', 'min:0'],
            'planOverageMetricKey' => ['nullable', 'string', 'max:255'],
            'planOverageUnit' => ['nullable', 'string', 'max:255'],
            'planOverageAmountMinor' => ['nullable', 'integer', 'min:0'],
            'planTargetMarginPercent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    protected function priceRules(): array
    {
        return [
            'pricePlanId' => [
                'required',
                Rule::exists('product_plans', 'id')->where(fn ($query) => $query->where('product_id', $this->product->id)),
            ],
            'priceCurrency' => ['required', 'string', 'size:3'],
            'priceAmount' => ['required', 'integer', 'min:0'],
            'priceBillingCycle' => [Rule::enum(ProductPriceBillingCycle::class)],
            'priceIsActive' => ['required', 'boolean'],
        ];
    }

    #[Layout('layouts.app')]
    #[Title('Product Details')]
    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->name = $product->name;
        $this->slug = $product->slug;
        $this->description = $product->description;
        $this->category = $product->category;
        $this->editStatus = $product->status;
    }

    public function updatedName(): void
    {
        if (blank($this->slug)) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function updatedSlug(): void
    {
        $this->slug = Str::slug($this->slug);
    }

    public function updatedPlanName(): void
    {
        if (blank($this->planSlug)) {
            $this->planSlug = Str::slug($this->planName);
        }

        if (blank($this->planSku)) {
            $this->planSku = strtoupper("{$this->product->slug}_{$this->planSlug}");
        }
    }

    public function updatedPlanSlug(): void
    {
        $this->planSlug = Str::slug($this->planSlug);

        if (blank($this->planSku)) {
            $this->planSku = strtoupper("{$this->product->slug}_{$this->planSlug}");
        }
    }

    public function updatedPlanSku(): void
    {
        $this->planSku = strtoupper($this->planSku);
    }

    public function updatedPlanIncludedUnit(): void
    {
        $this->planIncludedUnit = Str::snake($this->planIncludedUnit);
    }

    public function updatedPlanOverageMetricKey(): void
    {
        $this->planOverageMetricKey = Str::snake($this->planOverageMetricKey);
    }

    public function updatedPlanOverageUnit(): void
    {
        $this->planOverageUnit = Str::snake($this->planOverageUnit);
    }

    public function updatedPriceCurrency(): void
    {
        $this->priceCurrency = strtoupper($this->priceCurrency);
    }

    public function saveProduct(): void
    {
        $this->validate($this->productRules());

        if ($this->editStatus === ProductStatus::Active) {
            app(ProductIntegrityChecker::class)->assertProductCanPublish($this->product);
        }

        $this->product->update([
            'name' => $this->name,
            'slug' => Str::slug($this->slug),
            'description' => $this->description,
            'category' => $this->category,
            'status' => $this->editStatus,
        ]);

        $this->showEditForm = false;
        session()->flash('success', __('Product updated successfully.'));
    }

    public function openEditForm(): void
    {
        $this->showEditForm = true;
    }

    public function deleteProduct(): mixed
    {
        $productName = $this->product->name;

        $this->product->delete();

        session()->flash('success', __('Product ":name" deleted successfully.', ['name' => $productName]));

        return $this->redirectRoute('system.products.index', navigate: true);
    }

    public function cancelEdit(): void
    {
        $this->showEditForm = false;
        $this->name = $this->product->name;
        $this->slug = $this->product->slug;
        $this->description = $this->product->description;
        $this->category = $this->product->category;
        $this->editStatus = $this->product->status;
    }

    public function openAddEntitlementForm(): void
    {
        $this->resetEntitlementForm();
        $this->showAddEntitlementForm = true;
    }

    public function closeAddEntitlementForm(): void
    {
        $this->showAddEntitlementForm = false;
        $this->resetEntitlementForm();
    }

    public function addEntitlement(): void
    {
        $this->validate($this->entitlementRules());

        $payload = [
            'feature_key' => $this->newFeatureKey,
            'label' => $this->newLabel,
            'value' => $this->newValue,
            'type' => $this->newType,
            'description' => $this->newDescription,
            'is_active' => $this->entitlementIsActive,
        ];

        if ($this->editingEntitlementId !== null) {
            $this->findEntitlement($this->editingEntitlementId)->update($payload);
            session()->flash('success', __('Entitlement updated.'));
        } else {
            $this->product->entitlements()->create($payload);
            session()->flash('success', __('Entitlement added.'));
        }

        $this->closeAddEntitlementForm();
    }

    #[On('tree:open-edit-entitlement')]
    public function startEditEntitlement(int $entitlementId): void
    {
        $entitlement = $this->findEntitlement($entitlementId);

        $this->editingEntitlementId = $entitlement->id;
        $this->showAddEntitlementForm = true;
        $this->newFeatureKey = $entitlement->feature_key;
        $this->newLabel = $entitlement->label;
        $this->newValue = (string) ($entitlement->value ?? '');
        $this->newType = $entitlement->type;
        $this->newDescription = $entitlement->description;
        $this->entitlementIsActive = $entitlement->is_active;
        $this->resetErrorBag();
    }

    #[On('tree:toggle-entitlement')]
    public function toggleEntitlement(int $entitlementId): void
    {
        $entitlement = $this->findEntitlement($entitlementId);
        $entitlement->update([
            'is_active' => ! $entitlement->is_active,
        ]);

        session()->flash('success', $entitlement->fresh()->is_active ? __('Entitlement activated.') : __('Entitlement disabled.'));
    }

    #[On('tree:delete-entitlement')]
    public function deleteEntitlement(int $entitlementId): void
    {
        $this->findEntitlement($entitlementId)->delete();

        if ($this->editingEntitlementId === $entitlementId) {
            $this->closeAddEntitlementForm();
        }

        session()->flash('success', __('Entitlement removed.'));
    }

    protected function resetEntitlementForm(): void
    {
        $this->editingEntitlementId = null;
        $this->newFeatureKey = '';
        $this->newLabel = '';
        $this->newValue = '';
        $this->newType = EntitlementType::Text;
        $this->newDescription = '';
        $this->entitlementIsActive = true;
    }

    public function setFilterType(EntitlementType|string|null $type): void
    {
        $this->filterType = is_string($type) ? EntitlementType::from($type) : $type;
    }

    public function clearTypeFilter(): void
    {
        $this->filterType = null;
    }

    #[On('tree:open-add-limit')]
    public function openAddLimitForm(): void
    {
        $this->resetLimitForm();
        $this->showAddLimitForm = true;
        $this->editingLimitId = null;
    }

    #[On('tree:open-edit-limit')]
    public function startEditLimit(int $limitId): void
    {
        $limit = $this->findLimit($limitId);

        $this->editingLimitId = $limit->id;
        $this->showAddLimitForm = true;
        $this->limitKey = $limit->limit_key;
        $this->limitLabel = $limit->label;
        $this->limitValue = (string) $limit->value;
        $this->limitDescription = $limit->description;
        $this->limitIsActive = $limit->is_active;
        $this->resetErrorBag();
    }

    public function saveLimit(): void
    {
        $validated = $this->validate($this->limitRules());

        if ($this->editingLimitId !== null) {
            $this->findLimit($this->editingLimitId)->update([
                'limit_key' => $validated['limitKey'],
                'label' => $validated['limitLabel'],
                'value' => $validated['limitValue'],
                'description' => $validated['limitDescription'],
                'is_active' => $validated['limitIsActive'],
            ]);

            session()->flash('success', __('Limit updated.'));
        } else {
            $this->product->limits()->create([
                'limit_key' => $validated['limitKey'],
                'label' => $validated['limitLabel'],
                'value' => $validated['limitValue'],
                'description' => $validated['limitDescription'],
                'is_active' => $validated['limitIsActive'],
            ]);

            session()->flash('success', __('Limit added.'));
        }

        $this->resetLimitForm();
    }

    public function cancelLimitEdit(): void
    {
        $this->resetLimitForm();
    }

    #[On('tree:toggle-limit')]
    public function toggleLimit(int $limitId): void
    {
        $limit = $this->findLimit($limitId);
        $limit->update([
            'is_active' => ! $limit->is_active,
        ]);

        session()->flash('success', $limit->fresh()->is_active ? __('Limit activated.') : __('Limit disabled.'));
    }

    #[On('tree:delete-limit')]
    public function deleteLimit(int $limitId): void
    {
        $this->findLimit($limitId)->delete();

        if ($this->editingLimitId === $limitId) {
            $this->resetLimitForm();
        }

        session()->flash('success', __('Limit removed.'));
    }

    #[On('tree:open-add-feature')]
    public function openAddFeatureForm(): void
    {
        $this->resetFeatureForm();
        $this->showAddFeatureForm = true;
        $this->editingFeatureId = null;
    }

    #[On('tree:open-edit-feature')]
    public function startEditFeature(int $featureId): void
    {
        $feature = $this->findFeature($featureId);

        $this->editingFeatureId = $feature->id;
        $this->showAddFeatureForm = true;
        $this->featureKey = $feature->feature_key;
        $this->featureLabel = $feature->label;
        $this->featureValue = (string) ($feature->value ?? '');
        $this->featureDescription = $feature->description;
        $this->featureIsEnabled = $feature->is_enabled;
        $this->resetErrorBag();
    }

    public function saveFeature(): void
    {
        $validated = $this->validate($this->featureRules());

        if ($this->editingFeatureId !== null) {
            $this->findFeature($this->editingFeatureId)->update([
                'feature_key' => $validated['featureKey'],
                'label' => $validated['featureLabel'],
                'value' => $validated['featureValue'] ?: null,
                'description' => $validated['featureDescription'],
                'is_enabled' => $validated['featureIsEnabled'],
            ]);

            session()->flash('success', __('Feature updated.'));
        } else {
            $this->product->features()->create([
                'feature_key' => $validated['featureKey'],
                'label' => $validated['featureLabel'],
                'value' => $validated['featureValue'] ?: null,
                'description' => $validated['featureDescription'],
                'is_enabled' => $validated['featureIsEnabled'],
            ]);

            session()->flash('success', __('Feature added.'));
        }

        $this->resetFeatureForm();
    }

    public function cancelFeatureEdit(): void
    {
        $this->resetFeatureForm();
    }

    #[On('tree:toggle-feature')]
    public function toggleFeature(int $featureId): void
    {
        $feature = $this->findFeature($featureId);
        $feature->update([
            'is_enabled' => ! $feature->is_enabled,
        ]);

        session()->flash('success', $feature->fresh()->is_enabled ? __('Feature enabled.') : __('Feature disabled.'));
    }

    #[On('tree:delete-feature')]
    public function deleteFeature(int $featureId): void
    {
        $this->findFeature($featureId)->delete();

        if ($this->editingFeatureId === $featureId) {
            $this->resetFeatureForm();
        }

        session()->flash('success', __('Feature removed.'));
    }

    #[On('tree:open-add-plan')]
    public function openAddPlanForm(): void
    {
        $this->resetPlanForm();
        $this->showAddPlanForm = true;
        $this->editingPlanId = null;
    }

    #[On('tree:open-edit-plan')]
    public function startEditPlan(int $planId): void
    {
        $plan = $this->findPlan($planId);
        $limits = (array) data_get($plan->metadata, 'limits', []);
        $commercial = (array) data_get($plan->metadata, 'commercial', []);

        $this->editingPlanId = $plan->id;
        $this->showAddPlanForm = true;
        $this->planName = $plan->name;
        $this->planSlug = $plan->slug;
        $this->planSku = $plan->sku ?? '';
        $this->planDescription = $plan->description;
        $this->planIsActive = $plan->is_active;
        $this->planVoiceRsvpLimit = (string) data_get($limits, 'voice_rsvp_limit', '');
        $this->planVoiceMinutesLimit = (string) data_get($limits, 'voice_minutes_limit', '');
        $this->planIncludedUnit = (string) data_get($commercial, 'included_unit', '');
        $this->planIncludedQuantity = (string) data_get($commercial, 'included_quantity', '');
        $this->planOverageMetricKey = (string) data_get($commercial, 'overage_metric_key', '');
        $this->planOverageUnit = (string) data_get($commercial, 'overage_unit', '');
        $this->planOverageAmountMinor = (string) data_get($commercial, 'overage_amount_minor', '');
        $this->planTargetMarginPercent = (string) data_get($commercial, 'target_margin_percent', '');
        $this->resetErrorBag();
    }

    public function savePlan(): void
    {
        $this->planSlug = Str::slug($this->planSlug);
        $validated = $this->validate($this->planRules());

        if ($this->editingPlanId !== null) {
            $this->findPlan($this->editingPlanId)->update([
                'name' => $validated['planName'],
                'slug' => $validated['planSlug'],
                'sku' => $validated['planSku'],
                'description' => $validated['planDescription'],
                'is_active' => $validated['planIsActive'],
                'metadata' => $this->buildPlanMetadata($validated),
            ]);

            session()->flash('success', __('Plan updated.'));
        } else {
            $this->product->productPlans()->create([
                'name' => $validated['planName'],
                'slug' => $validated['planSlug'],
                'sku' => $validated['planSku'],
                'description' => $validated['planDescription'],
                'is_active' => $validated['planIsActive'],
                'metadata' => $this->buildPlanMetadata($validated),
            ]);

            session()->flash('success', __('Plan added.'));
        }

        $this->resetPlanForm();
    }

    public function cancelPlanEdit(): void
    {
        $this->resetPlanForm();
    }

    #[On('tree:toggle-plan')]
    public function togglePlan(int $planId): void
    {
        $plan = $this->findPlan($planId);
        $plan->update([
            'is_active' => ! $plan->is_active,
        ]);

        session()->flash('success', $plan->fresh()->is_active ? __('Plan activated.') : __('Plan deactivated.'));
    }

    public function reorderPlans(array $ids): void
    {
        foreach ($ids as $index => $id) {
            $this->product->productPlans()
                ->whereKey((int) $id)
                ->update(['sort_order' => $index]);
        }
    }

    #[On('tree:delete-plan')]
    public function deletePlan(int $planId): void
    {
        $this->findPlan($planId)->delete();

        if ($this->editingPlanId === $planId) {
            $this->resetPlanForm();
        }

        if ($this->pricePlanId === $planId) {
            $this->resetPriceForm();
        }

        session()->flash('success', __('Plan removed.'));
    }

    #[On('tree:open-add-price')]
    public function openAddPriceForm(int $planId): void
    {
        $this->findPlan($planId);
        $this->resetPriceForm();
        $this->showPriceForm = true;
        $this->pricePlanId = $planId;
        $this->editingPriceId = null;
    }

    public function startEditPrice(int $priceId): void
    {
        $price = $this->findPrice($priceId);

        $this->editingPriceId = $price->id;
        $this->showPriceForm = true;
        $this->pricePlanId = $price->product_plan_id;
        $this->priceCurrency = strtoupper($price->currency);
        $this->priceAmount = (string) $price->amount;
        $this->priceBillingCycle = $price->billing_cycle;
        $this->priceIsActive = $price->is_active;
        $this->resetErrorBag();
    }

    public function savePrice(): void
    {
        $this->priceCurrency = strtoupper($this->priceCurrency);
        $validated = $this->validate($this->priceRules());

        if ($this->editingPriceId !== null) {
            $this->findPrice($this->editingPriceId)->update([
                'product_plan_id' => $validated['pricePlanId'],
                'currency' => $validated['priceCurrency'],
                'amount' => (int) $validated['priceAmount'],
                'billing_cycle' => $validated['priceBillingCycle'],
                'is_active' => $validated['priceIsActive'],
            ]);

            session()->flash('success', __('Price updated.'));
        } else {
            ProductPrice::query()->create([
                'product_plan_id' => $validated['pricePlanId'],
                'currency' => $validated['priceCurrency'],
                'amount' => (int) $validated['priceAmount'],
                'billing_cycle' => $validated['priceBillingCycle'],
                'is_active' => $validated['priceIsActive'],
            ]);

            session()->flash('success', __('Price added.'));
        }

        $this->resetPriceForm();
    }

    public function cancelPriceEdit(): void
    {
        $this->resetPriceForm();
    }

    public function togglePrice(int $priceId): void
    {
        $price = $this->findPrice($priceId);
        $price->update([
            'is_active' => ! $price->is_active,
        ]);

        session()->flash('success', $price->fresh()->is_active ? __('Price activated.') : __('Price deactivated.'));
    }

    public function deletePrice(int $priceId): void
    {
        $price = $this->findPrice($priceId);
        $price->delete();

        if ($this->editingPriceId === $priceId) {
            $this->resetPriceForm();
        }

        session()->flash('success', __('Price removed.'));
    }

    protected function getFilterButtonClasses(EntitlementType $type, bool $isSelected): array
    {
        return match ($type) {
            EntitlementType::Boolean => $isSelected
                ? ['bgClass' => 'bg-indigo-100', 'textClass' => 'text-indigo-700', 'borderClass' => 'border-indigo-200']
                : ['bgClass' => 'bg-white', 'textClass' => 'text-slate-600', 'borderClass' => 'border-slate-200'],
            EntitlementType::Number => $isSelected
                ? ['bgClass' => 'bg-emerald-100', 'textClass' => 'text-emerald-700', 'borderClass' => 'border-emerald-200']
                : ['bgClass' => 'bg-white', 'textClass' => 'text-slate-600', 'borderClass' => 'border-slate-200'],
            EntitlementType::Text => $isSelected
                ? ['bgClass' => 'bg-amber-100', 'textClass' => 'text-amber-700', 'borderClass' => 'border-amber-200']
                : ['bgClass' => 'bg-white', 'textClass' => 'text-slate-600', 'borderClass' => 'border-slate-200'],
            EntitlementType::Enum => $isSelected
                ? ['bgClass' => 'bg-purple-100', 'textClass' => 'text-purple-700', 'borderClass' => 'border-purple-200']
                : ['bgClass' => 'bg-white', 'textClass' => 'text-slate-600', 'borderClass' => 'border-slate-200'],
        };
    }

    public function render(): View
    {
        $this->product = $this->product->fresh()->loadCount([
            'activeEntitlements as active_entitlements_count',
            'activeLimits as active_limits_count',
            'enabledFeatures as enabled_features_count',
            'productPlans as product_plans_count',
            'accountProducts as active_account_products_count' => fn ($query) => $query->active(),
        ])->load([
            'limits' => fn ($query) => $query->orderBy('limit_key'),
            'features' => fn ($query) => $query->orderBy('feature_key'),
            'productPlans' => fn ($query) => $query
                ->withCount(['activePrices', 'prices', 'subscriptions'])
                ->with([
                    'prices' => fn ($priceQuery) => $priceQuery->orderBy('amount'),
                ])
                ->orderBy('name'),
            'accountProducts' => fn ($query) => $query
                ->with('account')
                ->latest('granted_at')
                ->limit(6),
        ]);

        $entitlementsQuery = $this->product->productEntitlements();

        if ($this->filterType) {
            $entitlementsQuery->where('type', $this->filterType);
        }

        $filterButtonClasses = [];
        foreach (EntitlementType::cases() as $type) {
            $filterButtonClasses[$type->value] = $this->getFilterButtonClasses($type, $this->filterType === $type);
        }

        $recentUsageRecords = UsageRecord::query()
            ->with('account')
            ->where('product_id', $this->product->id)
            ->latest('recorded_at')
            ->limit(6)
            ->get();

        $pricingBasis = (array) data_get($this->product->metadata, 'commercial_model.pricing_basis', []);
        $pricingSources = (array) data_get($this->product->metadata, 'commercial_model.sources', []);

        return view('livewire.system.products.show', [
            'entitlements' => $entitlementsQuery->orderBy('feature_key')->get(),
            'filterButtonClasses' => $filterButtonClasses,
            'limits' => $this->product->limits,
            'features' => $this->product->features,
            'productPlans' => $this->product->productPlans()->with('prices')->get(),
            'recentAssignments' => $this->product->accountProducts,
            'recentUsageRecords' => $recentUsageRecords,
            'commercialInsights' => [
                'hasPricingModel' => $pricingBasis !== [],
                'assumedAverageCallMinutes' => data_get($pricingBasis, 'assumed_average_call_minutes'),
                'estimatedDirectCostUsdPerMinute' => data_get($pricingBasis, 'estimated_direct_cost_usd_per_minute_total'),
                'estimatedDirectCostUsdPerCall' => data_get($pricingBasis, 'estimated_direct_cost_usd_per_call'),
                'targetMarginPercent' => data_get($pricingBasis, 'target_margin_percent'),
                'costComponents' => (array) data_get($pricingBasis, 'estimated_direct_costs_usd_per_minute', []),
                'sources' => $pricingSources,
            ],
            'overview' => [
                'entitlements' => $this->product->active_entitlements_count,
                'limits' => $this->product->active_limits_count,
                'features' => $this->product->enabled_features_count,
                'plans' => $this->product->product_plans_count,
                'assignments' => $this->product->active_account_products_count,
                'usage_records' => $recentUsageRecords->count(),
            ],
        ]);
    }

    protected function resetLimitForm(): void
    {
        $this->reset([
            'limitKey',
            'limitLabel',
            'limitValue',
            'limitDescription',
            'editingLimitId',
        ]);

        $this->limitIsActive = true;
        $this->showAddLimitForm = false;
        $this->resetErrorBag();
    }

    protected function resetFeatureForm(): void
    {
        $this->reset([
            'featureKey',
            'featureLabel',
            'featureValue',
            'featureDescription',
            'editingFeatureId',
        ]);

        $this->featureIsEnabled = true;
        $this->showAddFeatureForm = false;
        $this->resetErrorBag();
    }

    protected function resetPlanForm(): void
    {
        $this->reset([
            'planName',
            'planSlug',
            'planSku',
            'planDescription',
            'editingPlanId',
            'planVoiceRsvpLimit',
            'planVoiceMinutesLimit',
            'planIncludedUnit',
            'planIncludedQuantity',
            'planOverageMetricKey',
            'planOverageUnit',
            'planOverageAmountMinor',
            'planTargetMarginPercent',
        ]);

        $this->planIsActive = true;
        $this->showAddPlanForm = false;
        $this->resetErrorBag();
    }

    protected function resetPriceForm(): void
    {
        $this->reset([
            'editingPriceId',
            'pricePlanId',
            'priceAmount',
        ]);

        $this->showPriceForm = false;
        $this->priceCurrency = 'USD';
        $this->priceBillingCycle = ProductPriceBillingCycle::Monthly;
        $this->priceIsActive = true;
        $this->resetErrorBag();
    }

    protected function findLimit(int $limitId): ProductLimit
    {
        return $this->product->limits()->whereKey($limitId)->firstOrFail();
    }

    protected function findFeature(int $featureId): ProductFeature
    {
        return $this->product->features()->whereKey($featureId)->firstOrFail();
    }

    protected function findPlan(int $planId): ProductPlan
    {
        return $this->product->productPlans()->whereKey($planId)->firstOrFail();
    }

    protected function findEntitlement(int $entitlementId): ProductEntitlement
    {
        return $this->product->productEntitlements()->whereKey($entitlementId)->firstOrFail();
    }

    protected function findPrice(int $priceId): ProductPrice
    {
        return ProductPrice::query()
            ->whereKey($priceId)
            ->whereHas('productPlan', fn ($query) => $query->where('product_id', $this->product->id))
            ->firstOrFail();
    }

    protected function buildPlanMetadata(array $validated): array
    {
        $limits = array_filter([
            'voice_rsvp_limit' => $validated['planVoiceRsvpLimit'] !== '' ? (int) $validated['planVoiceRsvpLimit'] : null,
            'voice_minutes_limit' => $validated['planVoiceMinutesLimit'] !== '' ? (int) $validated['planVoiceMinutesLimit'] : null,
        ], static fn ($value) => $value !== null);

        $commercial = array_filter([
            'included_unit' => $validated['planIncludedUnit'] ?: null,
            'included_quantity' => $validated['planIncludedQuantity'] !== '' ? (int) $validated['planIncludedQuantity'] : null,
            'overage_metric_key' => $validated['planOverageMetricKey'] ?: null,
            'overage_unit' => $validated['planOverageUnit'] ?: null,
            'overage_amount_minor' => $validated['planOverageAmountMinor'] !== '' ? (int) $validated['planOverageAmountMinor'] : null,
            'target_margin_percent' => $validated['planTargetMarginPercent'] !== '' ? (int) $validated['planTargetMarginPercent'] : null,
        ], static fn ($value) => $value !== null);

        $usagePolicies = [];

        if (($validated['planOverageMetricKey'] ?? '') !== '') {
            $usagePolicies[$validated['planOverageMetricKey']] = ['mode' => 'hard'];
        }

        if (($validated['planVoiceMinutesLimit'] ?? '') !== '') {
            $usagePolicies['voice_minutes'] = ['mode' => 'hard'];
        }

        return array_filter([
            'limits' => $limits !== [] ? $limits : null,
            'commercial' => $commercial !== [] ? $commercial : null,
            'usage_policies' => $usagePolicies !== [] ? $usagePolicies : null,
        ], static fn ($value) => $value !== null);
    }
}
