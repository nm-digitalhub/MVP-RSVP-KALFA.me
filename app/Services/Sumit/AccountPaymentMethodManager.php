<?php

declare(strict_types=1);

namespace App\Services\Sumit;

use App\Contracts\BillingProvider;
use App\Models\Account;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use OfficeGuy\LaravelSumitGateway\Http\Connectors\SumitConnector;
use OfficeGuy\LaravelSumitGateway\Http\DTOs\CredentialsData;
use OfficeGuy\LaravelSumitGateway\Http\Requests\Payment\ChargePaymentRequest;
use OfficeGuy\LaravelSumitGateway\Models\OfficeGuyToken;
use OfficeGuy\LaravelSumitGateway\Services\PaymentService;

class AccountPaymentMethodManager
{
    public function __construct(
        private readonly BillingProvider $billingProvider,
    ) {}

    public function storeSingleUseToken(Account $account, string $singleUseToken): OfficeGuyToken
    {
        $singleUseToken = trim($singleUseToken);

        if ($singleUseToken === '') {
            throw new \RuntimeException('A SUMIT payment token is required before saving a payment method.');
        }

        $this->billingProvider->createCustomer($account);

        if ($account->sumit_customer_id === null) {
            throw new \RuntimeException('SUMIT customer synchronization did not return a valid customer ID.');
        }

        $connector = new SumitConnector;

        $request = new ChargePaymentRequest(
            customerId: $account->sumit_customer_id,
            amount: 1,
            credentials: $this->credentials(),
            singleUseToken: $singleUseToken,
            description: 'Token authorization charge',
            cancelable: true
        );

        $response = $connector->send($request)->json();

        Log::info('SUMIT Tokenization Response', [
            'account_id' => $account->id,
            'status' => $response['Status'] ?? null,
            'valid_payment' => Arr::get($response, 'Data.Payment.ValidPayment'),
            'auth_number' => Arr::get($response, 'Data.Payment.AuthNumber'),
            'has_token' => Arr::has($response, 'Data.Payment.PaymentMethod.CreditCard_Token'),
            'token' => Arr::get($response, 'Data.Payment.PaymentMethod.CreditCard_Token'),
            'full_response' => $response,
        ]);

        $success = ($response['Status'] ?? null) === 0;
        $validPayment = Arr::get($response, 'Data.Payment.ValidPayment', false);

        if (! $success || ! $validPayment) {
            $message = Arr::get($response, 'UserErrorMessage')
                ?? Arr::get($response, 'Data.ResultDescription')
                ?? 'Failed to authorize payment method with SUMIT.';

            throw new \RuntimeException((string) $message);
        }

        $cardToken = Arr::get(
            $response,
            'Data.Payment.PaymentMethod.CreditCard_Token'
        );

        if (! $cardToken) {
            throw new \RuntimeException('SUMIT did not return a card token.');
        }

        $paymentMethod = Arr::get($response, 'Data.Payment.PaymentMethod', []);

        $lastFour = (string) ($paymentMethod['CreditCard_LastDigits'] ?? '');
        $expiryMonth = str_pad((string) ($paymentMethod['CreditCard_ExpirationMonth'] ?? '1'), 2, '0', STR_PAD_LEFT);
        $expiryYear = (string) ($paymentMethod['CreditCard_ExpirationYear'] ?? date('Y'));
        $citizenId = $paymentMethod['CreditCard_CitizenID'] ?? null;

        // Reset default for other tokens
        OfficeGuyToken::query()
            ->where('owner_type', $account->getMorphClass())
            ->where('owner_id', $account->getKey())
            ->update(['is_default' => false]);

        // Handle existing soft-deleted token to prevent "Unique violation"
        $existing = OfficeGuyToken::withTrashed()
            ->where('token', $cardToken)
            ->first();

        if ($existing) {
            if ($existing->owner_type !== $account->getMorphClass() || (string) $existing->owner_id !== (string) $account->getKey()) {
                throw new \RuntimeException('This card is already registered to another account.');
            }

            if ($existing->trashed()) {
                $existing->restore();
            }
        }

        // Build the token directly from the charge response — no extra gettransaction call needed.
        // All required fields (token, last digits, expiry, citizen ID) are returned by /billing/payments/charge/.
        $token = OfficeGuyToken::updateOrCreate(
            [
                'token' => $cardToken,
                'owner_type' => $account->getMorphClass(),
                'owner_id' => $account->getKey(),
            ],
            [
                'gateway_id' => 'officeguy',
                'card_type' => 'card',
                'last_four' => $lastFour,
                'citizen_id' => $citizenId,
                'expiry_month' => $expiryMonth,
                'expiry_year' => $expiryYear,
                'is_default' => true,
                'metadata' => $paymentMethod,
            ]
        );

        return $token;
    }

    public function setDefault(Account $account, OfficeGuyToken $token): void
    {
        $this->assertOwnership($account, $token);
        $this->billingProvider->createCustomer($account);

        if ($account->sumit_customer_id === null) {
            throw new \RuntimeException('SUMIT customer synchronization did not return a valid customer ID.');
        }

        $result = PaymentService::setPaymentMethodForCustomer(
            $account->sumit_customer_id,
            $token->token,
            [
                'CreditCard_ExpirationMonth' => (int) $token->expiry_month,
                'CreditCard_ExpirationYear' => (int) $token->expiry_year,
            ],
        );

        if (! ($result['success'] ?? false)) {
            throw new \RuntimeException((string) ($result['error'] ?? 'Failed to update the default payment method in SUMIT.'));
        }

        $token->setAsDefault();
    }

    public function delete(Account $account, OfficeGuyToken $token): void
    {
        $this->assertOwnership($account, $token);

        $replacement = $account->paymentMethods()
            ->whereKeyNot($token->getKey())
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();

        if ($token->is_default && $account->sumit_customer_id !== null) {
            if ($replacement instanceof OfficeGuyToken) {
                $replacementResult = PaymentService::setPaymentMethodForCustomer(
                    $account->sumit_customer_id,
                    $replacement->token,
                    [
                        'CreditCard_ExpirationMonth' => (int) $replacement->expiry_month,
                        'CreditCard_ExpirationYear' => (int) $replacement->expiry_year,
                    ],
                );

                if (! ($replacementResult['success'] ?? false)) {
                    throw new \RuntimeException((string) ($replacementResult['error'] ?? 'Failed to switch the default payment method in SUMIT.'));
                }
            } else {
                $removeResult = PaymentService::removePaymentMethodForCustomer($account->sumit_customer_id);

                if (! ($removeResult['success'] ?? false)) {
                    throw new \RuntimeException((string) ($removeResult['error'] ?? 'Failed to remove the active payment method from SUMIT.'));
                }
            }
        }

        $token->delete();

        if ($replacement instanceof OfficeGuyToken) {
            $replacement->setAsDefault();
        }
    }

    private function credentials(): CredentialsData
    {
        return new CredentialsData(
            companyId: (int) config('officeguy.company_id'),
            apiKey: (string) config('officeguy.private_key'),
        );
    }

    private function assertOwnership(Account $account, OfficeGuyToken $token): void
    {
        if ($token->owner_type !== $account::class || (int) $token->owner_id !== (int) $account->getKey()) {
            throw new \RuntimeException('The selected payment method does not belong to this account.');
        }
    }
}
