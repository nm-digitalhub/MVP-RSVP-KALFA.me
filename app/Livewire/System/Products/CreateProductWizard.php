<?php

declare(strict_types=1);

namespace App\Livewire\System\Products;

use App\Enums\EntitlementType;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductIntegrityChecker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Transition;
use Livewire\Component;
use OfficeGuy\LaravelSumitGateway\Services\SumitProductService;

#[Layout('layouts.app')]
#[Title('Create product')]
final class CreateProductWizard extends Component
{
    public int $step = 1;

    public int $totalSteps = 5;

    public ?Product $product = null;

    public string $name = '';

    public string $slug = '';

    public ?string $description = '';

    public ?string $category = '';

    public string $entitlementFeatureKey = '';

    public string $entitlementLabel = '';

    public string $entitlementValue = '';

    public EntitlementType $entitlementType = EntitlementType::Text;

    public ?string $entitlementDescription = '';

    public string $limitKey = '';

    public string $limitLabel = '';

    public string $limitValue = '';

    public ?string $limitDescription = '';

    public bool $limitIsActive = true;

    public string $featureKey = '';

    public string $featureLabel = '';

    public string $featureValue = '';

    public ?string $featureDescription = '';

    public bool $featureIsEnabled = true;

    // Plans and Pricing (Step 4)
    public string $planName = '';

    public string $planSlug = '';

    public string $planSku = '';

    public ?string $planDescription = '';

    public bool $planIsActive = true;

    public string $planCurrency = 'USD';

    public string $planAmount = '';

    public string $planBillingCycle = 'monthly';

    #[Transition(type: 'forward')]
    public function nextStep(): mixed
    {
        if ($this->step === 1) {
            $this->persistProduct();
        }

        if ($this->step >= $this->totalSteps) {
            return $this->publish();
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

    public function updated(string $property): void
    {
        if ($property === 'slug') {
            $this->slug = Str::slug($this->slug);
        }

        if (in_array($property, ['name', 'slug', 'description', 'category'], true)) {
            $this->validateOnly($property, $this->productRules());
        }

        if (in_array($property, ['entitlementFeatureKey', 'entitlementLabel', 'entitlementValue', 'entitlementType', 'entitlementDescription'], true) && $this->product !== null) {
            $this->validateOnly($property, $this->entitlementRules());
        }

        if (in_array($property, ['limitKey', 'limitLabel', 'limitValue', 'limitDescription', 'limitIsActive'], true) && $this->product !== null) {
            $this->validateOnly($property, $this->limitRules());
        }

        if (in_array($property, ['featureKey', 'featureLabel', 'featureValue', 'featureDescription', 'featureIsEnabled'], true) && $this->product !== null) {
            $this->validateOnly($property, $this->featureRules());
        }

if (in_array($property, ['planName', 'planSlug', 'planSku', 'planDescription', 'planAmount', 'planCurrency'], true) && $this->product !== null) {
if ($property === 'planSlug') {
        $this->planSlug = Str::slug($this->planSlug);
    }
    $this->validateOnly($property, $this->planRules());
}
    }

    public function updatedPlanName(): void
    {
        if (empty($this->planSlug)) {
            $this->planSlug = Str::slug($this->planName);
        }

        if (empty($this->planSku)) {
            $productSlug = $this->product?->slug ?? $this->slug;
            $this->planSku = strtoupper("{$productSlug}_{$this->planSlug}");
        }
    }

    public function updatedPlanSlug(): void
    {
        $this->planSlug = Str::slug($this->planSlug);

        if (empty($this->planSku)) {
            $productSlug = $this->product?->slug ?? $this->slug;
            $this->planSku = strtoupper("{$productSlug}_{$this->planSlug}");
        }
    }

    public function updatedPlanSku(): void
    {
        $this->planSku = strtoupper($this->planSku);
    }

    public function generateSlug(): void
    {
        $this->slug = (string) Str::slug($this->slug);
        $this->validateOnly('slug', $this->productRules());
    }

    public function addEntitlement(): void
    {
        $product = $this->requireProduct();
        $validated = $this->validate($this->entitlementRules());

        $product->entitlements()->create([
            'feature_key' => $validated['entitlementFeatureKey'],
            'label' => $validated['entitlementLabel'],
            'value' => $validated['entitlementValue'] ?: null,
            'type' => $validated['entitlementType'],
            'description' => $validated['entitlementDescription'] ?: null,
            'is_active' => true,
        ]);

        $this->resetEntitlementForm();
        $this->product = $product->fresh();
        session()->flash('success', __('Entitlement added to draft product.'));
    }

    public function removeEntitlement(int $entitlementId): void
    {
        $this->requireProduct()
            ->entitlements()
            ->whereKey($entitlementId)
            ->delete();

        $this->product = $this->product?->fresh();
        session()->flash('success', __('Entitlement removed.'));
    }

    public function addLimit(): void
    {
        if (! $this->supportsLimits()) {
            $this->addError('limits', __('Product limits are not available until the latest database migrations are run.'));

            return;
        }

        $product = $this->requireProduct();
        $validated = $this->validate($this->limitRules());

        $product->limits()->create([
            'limit_key' => $validated['limitKey'],
            'label' => $validated['limitLabel'],
            'value' => $validated['limitValue'],
            'description' => $validated['limitDescription'] ?: null,
            'is_active' => $validated['limitIsActive'],
        ]);

        $this->resetLimitForm();
        $this->product = $product->fresh();
        session()->flash('success', __('Limit added to draft product.'));
    }

    public function toggleLimit(int $limitId): void
    {
        if (! $this->supportsLimits()) {
            return;
        }

        $limit = $this->requireProduct()
            ->limits()
            ->find($limitId);

        if ($limit) {
            $limit->update(['is_active' => ! $limit->is_active]);
        }

        $this->product = $this->product?->fresh();
    }

    public function removeLimit(int $limitId): void
    {
        if (! $this->supportsLimits()) {
            return;
        }

        $this->requireProduct()
            ->limits()
            ->whereKey($limitId)
            ->delete();

        $this->product = $this->product?->fresh();
        session()->flash('success', __('Limit removed.'));
    }

    public function addFeature(): void
    {
        if (! $this->supportsFeatures()) {
            $this->addError('features', __('Product features are not available until the latest database migrations are run.'));

            return;
        }

        $product = $this->requireProduct();
        $validated = $this->validate($this->featureRules());

        $product->features()->create([
            'feature_key' => $validated['featureKey'],
            'label' => $validated['featureLabel'],
            'value' => $validated['featureValue'] ?: null,
            'description' => $validated['featureDescription'] ?: null,
            'is_enabled' => $validated['featureIsEnabled'],
        ]);

        $this->resetFeatureForm();
        $this->product = $product->fresh();
        session()->flash('success', __('Feature configuration added to draft product.'));
    }

    public function toggleFeature(int $featureId): void
    {
        if (! $this->supportsFeatures()) {
            return;
        }

        $feature = $this->requireProduct()
            ->features()
            ->find($featureId);

        if ($feature) {
            $feature->update(['is_enabled' => ! $feature->is_enabled]);
        }

        $this->product = $this->product?->fresh();
    }

    public function removeFeature(int $featureId): void
    {
        if (! $this->supportsFeatures()) {
            return;
        }

        $this->requireProduct()
            ->features()
            ->whereKey($featureId)
            ->delete();

        $this->product = $this->product?->fresh();
        session()->flash('success', __('Feature removed.'));
    }

   public function addPlan(): void
{
    $product = $this->requireProduct();
    $validated = $this->validate($this->planRules());

$plan = $product->productPlans()->create([
    'name' => $validated['planName'],
    'slug' => $validated['planSlug'],
    'sku' => $validated['planSku'] ?: null,
    'description' => $validated['planDescription'] ?: null,
    'is_active' => $this->planIsActive,
]);

    if (! empty($validated['planAmount'])) {
        $plan->prices()->create([
            'currency' => $validated['planCurrency'],
            'amount' => (int) $validated['planAmount'],
            'billing_cycle' => $this->planBillingCycle,
            'is_active' => true,
        ]);
    }

    $sumitResponse = SumitProductService::createProduct(
        name: $plan->name,
        sku: $plan->sku ?? '',
        price: ((float) ($validated['planAmount'] ?: 0)) / 100,
        description: $plan->description
    );

    if (! $sumitResponse['success']) {
        $this->addError('plan', __('The plan was created locally, but failed to sync to SUMIT: :error', [
            'error' => $sumitResponse['error'] ?? __('Unknown error'),
        ]));

        session()->flash('error', __('Plan added to draft product, but SUMIT sync failed.'));
    } elseif (isset($sumitResponse['sumit_entity_id'])) {
        $plan->update([
            'sumit_entity_id' => $sumitResponse['sumit_entity_id'],
        ]);

        session()->flash('success', __('Plan added to draft product and synced to SUMIT.'));
    } else {
        session()->flash('success', __('Plan added to draft product.'));
    }

    $this->resetPlanForm();
    $this->product = $product->fresh();
}

    public function removePlan(int $planId): void
    {
        $this->requireProduct()
            ->productPlans()
            ->whereKey($planId)
            ->delete();

        $this->product = $this->product?->fresh();
        session()->flash('success', __('Plan removed.'));
    }

    public function reorderPlans(array $ids): void
    {
        foreach ($ids as $index => $id) {
            $this->requireProduct()
                ->productPlans()
                ->whereKey((int) $id)
                ->update(['sort_order' => $index]);
        }

        $this->product = $this->product?->fresh();
    }

    public function publish(): mixed
    {
        $product = $this->requireProduct();
        app(ProductIntegrityChecker::class)->assertProductCanPublish($product);

        $product->update([
            'status' => ProductStatus::Active,
        ]);

        session()->flash('success', __('Product published successfully.'));

        return $this->redirectRoute('system.products.show', ['product' => $product], navigate: true);
    }


    public function render(): View
    {
        return view('livewire.system.products.create-product-wizard', [
            'entitlements' => $this->product?->entitlements()->orderBy('feature_key')->get() ?? new Collection,
            'limits' => $this->supportsLimits()
                ? ($this->product?->limits()->orderBy('limit_key')->get() ?? new Collection)
                : new Collection,
            'features' => $this->supportsFeatures()
                ? ($this->product?->features()->orderBy('feature_key')->get() ?? new Collection)
                : new Collection,
            'supportsLimits' => $this->supportsLimits(),
            'supportsFeatures' => $this->supportsFeatures(),
        ]);
    }

    protected function productRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($this->product?->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function entitlementRules(): array
    {
        return [
            'entitlementFeatureKey' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_entitlements', 'feature_key')
                    ->where(fn ($query) => $query->where('product_id', $this->requireProduct()->id)),
            ],
            'entitlementLabel' => ['required', 'string', 'max:255'],
            'entitlementValue' => ['nullable', 'string', 'max:255'],
            'entitlementType' => [Rule::enum(EntitlementType::class)],
            'entitlementDescription' => ['nullable', 'string', 'max:1000'],
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
                    ->where(fn ($query) => $query->where('product_id', $this->requireProduct()->id)),
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
                    ->where(fn ($query) => $query->where('product_id', $this->requireProduct()->id)),
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
                    ->where(fn ($query) => $query->where('product_id', $this->requireProduct()->id)),
            ],
            'planDescription' => ['nullable', 'string', 'max:1000'],
            'planSku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_plans', 'sku'),
            ],
            'planCurrency' => ['required', 'string', 'size:3'],
            'planAmount' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function persistProduct(): void
    {
        $this->slug = Str::slug($this->slug);

        $validated = $this->validate($this->productRules());
        $payload = [
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'description' => $validated['description'] ?: null,
            'category' => $validated['category'] ?: null,
        ];

        if ($this->product === null) {
            $this->product = Product::create([
                ...$payload,
                'status' => ProductStatus::Draft,
            ]);

            session()->flash('success', __('Draft product created. Continue configuring it in the next steps.'));

            return;
        }

        $this->product->update($payload);
        $this->product = $this->product->fresh();
        session()->flash('success', __('Draft product info updated.'));
    }

    protected function requireProduct(): Product
    {
        abort_if($this->product === null, 404);

        return $this->product;
    }

    protected function supportsLimits(): bool
    {
        return Schema::hasTable('product_limits');
    }

    protected function supportsFeatures(): bool
    {
        return Schema::hasTable('product_features');
    }

    protected function resetEntitlementForm(): void
    {
        $this->reset([
            'entitlementFeatureKey',
            'entitlementLabel',
            'entitlementValue',
            'entitlementDescription',
        ]);

        $this->entitlementType = EntitlementType::Text;
    }

    protected function resetLimitForm(): void
    {
        $this->reset([
            'limitKey',
            'limitLabel',
            'limitValue',
            'limitDescription',
        ]);

        $this->limitIsActive = true;
    }

    protected function resetFeatureForm(): void
    {
        $this->reset([
            'featureKey',
            'featureLabel',
            'featureValue',
            'featureDescription',
        ]);

        $this->featureIsEnabled = true;
    }

    protected function resetPlanForm(): void
{
    $this->reset([
        'planName',
        'planSlug',
        'planSku',
        'planDescription',
        'planAmount',
    ]);

    $this->planIsActive = true;
    $this->planCurrency = 'USD';
    $this->planBillingCycle = 'monthly';
}
}
