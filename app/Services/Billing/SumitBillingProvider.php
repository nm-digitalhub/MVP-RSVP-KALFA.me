<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\BillingProvider;
use App\Enums\ProductPriceBillingCycle;
use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\ProductPrice;
use App\Services\Sumit\SumitUsageChargePayable;
use Illuminate\Support\Facades\Log;
use OfficeGuy\LaravelSumitGateway\DataTransferObjects\CustomerData;
use OfficeGuy\LaravelSumitGateway\Http\Connectors\SumitConnector;
use OfficeGuy\LaravelSumitGateway\Http\DTOs\CredentialsData;
use OfficeGuy\LaravelSumitGateway\Http\Requests\Customer\CreateCustomerRequest;
use OfficeGuy\LaravelSumitGateway\Models\OfficeGuyToken;
use OfficeGuy\LaravelSumitGateway\Models\Subscription as OfficeGuySubscription;
use OfficeGuy\LaravelSumitGateway\Services\PaymentService;
use OfficeGuy\LaravelSumitGateway\Services\SubscriptionService as OfficeGuySubscriptionService;

final class SumitBillingProvider implements BillingProvider
{
    public function createCustomer(Account $account): array
    {
        $account->loadMissing('owner');

        if ($account->sumit_customer_id !== null) {
            return [
                'provider' => 'sumit',
                'customer_reference' => (string) $account->sumit_customer_id,
                'metadata' => [
                    'sumit_customer_id' => $account->sumit_customer_id,
                    'existing' => true,
                ],
            ];
        }

        $customer = $this->customerPayload($account);
        $credentials = $this->credentials();
        $connector = new SumitConnector;

        $request = new CreateCustomerRequest($customer, $credentials);
        $response = $connector->send($request)->json();

        if (($response['Status'] ?? 1) !== 0) {
            throw new \RuntimeException($response['UserErrorMessage'] ?? 'Failed to create SUMIT customer.');
        }

        $customerId = (int) ($response['Data']['CustomerID'] ?? 0);

        if ($customerId < 1) {
            throw new \RuntimeException('SUMIT customer creation did not return a valid customer ID.');
        }

        $account->forceFill([
            'sumit_customer_id' => $customerId,
        ])->save();

        return [
            'provider' => 'sumit',
            'customer_reference' => (string) $customerId,
            'metadata' => [
                'sumit_customer_id' => $customerId,
                'existing' => false,
            ],
        ];
    }

    public function createSubscription(Account $account, ProductPrice $price): array
    {
        $account->loadMissing('owner');
        $price->loadMissing('productPlan.product');

        $customer = $this->createCustomer($account);
        $billingCycle = $price->billing_cycle ?? ProductPriceBillingCycle::Monthly;

        if ($billingCycle === ProductPriceBillingCycle::Usage) {
            return [
                'provider' => 'sumit',
                'subscription_reference' => 'usage-reconciliation',
                'customer_reference' => $customer['customer_reference'],
                'metadata' => [
                    'sumit_customer_id' => (int) $customer['customer_reference'],
                    'usage_reconciliation_required' => true,
                    'product_price_id' => $price->id,
                ],
            ];
        }

        $defaultToken = $this->defaultTokenFor($account);

        if ($defaultToken === null) {
            throw new \RuntimeException('SUMIT subscription requires a default OfficeGuy payment token for the account.');
        }

        $officeGuySubscription = OfficeGuySubscriptionService::create(
            $account,
            $price->productPlan->name,
            $price->amount / 100,
            $price->currency,
            $billingCycle === ProductPriceBillingCycle::Yearly ? 12 : 1,
            null,
            $defaultToken->id,
            [
                'account_id' => $account->id,
                'product_id' => $price->productPlan->product_id,
                'product_plan_id' => $price->product_plan_id,
                'product_price_id' => $price->id,
            ],
        );

        $result = PaymentService::processCharge(
            $officeGuySubscription,
            1,
            true,
            false,
            $defaultToken,
            [
                'Customer' => [
                    'ID' => (int) $customer['customer_reference'],
                ],
            ],
        );

        if (! ($result['success'] ?? false)) {
            $officeGuySubscription->markAsFailed();

            throw new \RuntimeException($result['message'] ?? 'Failed to create SUMIT recurring subscription.');
        }

        $recurringId = $result['response']['Data']['RecurringID']
            ?? $result['response']['Data']['Payment']['RecurringID']
            ?? null;

        $officeGuySubscription->activate();
        $officeGuySubscription->recordCharge(is_string($recurringId) ? $recurringId : null);

        return [
            'provider' => 'sumit',
            'subscription_reference' => (string) ($recurringId ?? $officeGuySubscription->id),
            'customer_reference' => $customer['customer_reference'],
            'metadata' => [
                'officeguy_subscription_id' => $officeGuySubscription->id,
                'recurring_id' => $recurringId,
                'officeguy_token_id' => $defaultToken->id,
                'sumit_customer_id' => (int) $customer['customer_reference'],
                'product_price_id' => $price->id,
            ],
        ];
    }

    public function cancelSubscription(AccountSubscription $subscription): void
    {
        $officeGuySubscriptionId = (int) data_get($subscription->metadata, 'billing.officeguy_subscription_id', 0);

        if ($officeGuySubscriptionId < 1) {
            Log::warning('SUMIT billing cancellation skipped because no OfficeGuy subscription ID was recorded.', [
                'account_subscription_id' => $subscription->id,
            ]);

            return;
        }

        $officeGuySubscription = OfficeGuySubscription::query()->find($officeGuySubscriptionId);

        if ($officeGuySubscription === null) {
            Log::warning('SUMIT billing cancellation skipped because the OfficeGuy subscription was not found.', [
                'account_subscription_id' => $subscription->id,
                'officeguy_subscription_id' => $officeGuySubscriptionId,
            ]);

            return;
        }

        OfficeGuySubscriptionService::cancel(
            $officeGuySubscription,
            sprintf('Cancelled from account subscription %d', $subscription->id),
        );
    }

    public function reportUsage(AccountSubscription $subscription, string $metric, int $quantity, array $context = []): array
    {
        $subscription->loadMissing('account', 'productPlan.product');

        $amountMinor = (int) ($context['amount_minor'] ?? 0);

        if ($amountMinor < 1) {
            return [
                'provider' => 'sumit',
                'charged' => false,
                'metadata' => [
                    'reason' => 'no_billable_overage',
                ],
            ];
        }

        $customer = $this->createCustomer($subscription->account);
        $defaultToken = $this->defaultTokenFor($subscription->account);

        if ($defaultToken === null) {
            throw new \RuntimeException('SUMIT usage billing requires a default OfficeGuy payment token for the account.');
        }

        $payable = new SumitUsageChargePayable(
            account: $subscription->account,
            amountMinor: $amountMinor,
            currency: (string) ($context['currency'] ?? 'ILS'),
            metricKey: $metric,
            quantity: $quantity,
            description: sprintf(
                '%s overage for %s',
                $subscription->productPlan->name,
                str_replace('_', ' ', $metric),
            ),
            reference: (string) ($context['usage_record_id'] ?? $subscription->id),
            unit: isset($context['unit']) ? (string) $context['unit'] : null,
        );

        $result = PaymentService::processCharge(
            $payable,
            1,
            false,
            false,
            $defaultToken,
            [
                'Customer' => [
                    'ID' => (int) $customer['customer_reference'],
                ],
            ],
        );

        if (! ($result['success'] ?? false)) {
            $officeGuySubscription->markAsFailed();

            return [
                'provider' => 'sumit',
                'subscription_reference' => null,
                'customer_reference' => $customer['customer_reference'],
                'failed' => true,
                'error' => $result['message'] ?? 'Payment failed',
                'metadata' => [
                    'officeguy_subscription_id' => $officeGuySubscription->id,
                    'sumit_customer_id' => (int) $customer['customer_reference'],
                    'product_price_id' => $price->id,
                ],
            ];
        }

        $paymentId = $result['response']['Data']['PaymentID']
            ?? $result['response']['Data']['Payment']['ID']
            ?? $result['response']['Data']['TransactionID']
            ?? null;

        Log::info('SUMIT usage overage charged.', [
            'account_subscription_id' => $subscription->id,
            'metric' => $metric,
            'quantity' => $quantity,
            'amount_minor' => $amountMinor,
            'payment_id' => $paymentId,
        ]);

        return [
            'provider' => 'sumit',
            'charged' => true,
            'charge_reference' => $paymentId !== null ? (string) $paymentId : null,
            'metadata' => [
                'officeguy_token_id' => $defaultToken->id,
                'sumit_customer_id' => (int) $customer['customer_reference'],
            ],
        ];
    }

    private function customerPayload(Account $account): CustomerData
    {
        $email = $account->getSumitCustomerEmail();

        if (blank($email)) {
            throw new \RuntimeException(sprintf('Account %d must have an owner email before syncing to SUMIT.', $account->id));
        }

        return new CustomerData(
            name: $account->getSumitCustomerName() ?? sprintf('Account %d', $account->id),
            email: $email,
            phone: $account->getSumitCustomerPhone(),
            address: null,
            city: null,
            zipCode: null,
            companyNumber: $account->getSumitCustomerBusinessId(),
        );
    }

    private function credentials(): CredentialsData
    {
        return new CredentialsData(
            companyId: (int) config('officeguy.company_id'),
            apiKey: (string) config('officeguy.private_key'),
        );
    }

    private function defaultTokenFor(Account $account): ?OfficeGuyToken
    {
        return OfficeGuyToken::query()
            ->where('owner_type', $account::class)
            ->where('owner_id', $account->getKey())
            ->where('is_default', true)
            ->latest('id')
            ->first()
            ?? OfficeGuyToken::query()
                ->where('owner_type', $account::class)
                ->where('owner_id', $account->getKey())
                ->latest('id')
                ->first();
    }
}
