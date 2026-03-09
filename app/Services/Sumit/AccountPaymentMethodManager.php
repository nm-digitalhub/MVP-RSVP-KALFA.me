<?php

declare(strict_types=1);

namespace App\Services\Sumit;

use App\Contracts\BillingProvider;
use App\Models\Account;
use Illuminate\Support\Arr;
use OfficeGuy\LaravelSumitGateway\Http\Connectors\SumitConnector;
use OfficeGuy\LaravelSumitGateway\Http\DTOs\CredentialsData;
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
        $paymentData = new \OfficeGuy\LaravelSumitGateway\Http\DTOs\PaymentData(
            amount: 1,
            orderId: 'AUTH-'.time().'-'.rand(100, 999),
            singleUseToken: $singleUseToken,
            paramJ: (string) config('officeguy.token_param', '5'),
            description: 'Token authorization charge'
        );

        $request = new \OfficeGuy\LaravelSumitGateway\Http\Requests\Payment\CreatePaymentRequest(
            payment: $paymentData,
            credentials: $this->credentials(),
        );

        $response = $connector->send($request)->json();

        \Illuminate\Support\Facades\Log::info('SUMIT Tokenization Response', [
            'account_id' => $account->id,
            'status' => $response['Status'] ?? 'N/A',
            'success' => $response['Data']['Success'] ?? 'N/A',
            'has_token' => isset($response['Data']['CardToken']) || isset($response['Data']['Token']),
            'full_response' => $response,
        ]);

        $success = Arr::get($response, 'Data.Success', false);

        if (! $success) {
            $message = Arr::get($response, 'UserErrorMessage')
                ?? Arr::get($response, 'Data.ResultDescription')
                ?? 'Failed to store payment method via transaction.';

            throw new \RuntimeException((string) $message);
        }

        // Reset default for other tokens
        OfficeGuyToken::query()
            ->where('owner_type', $account->getMorphClass())
            ->where('owner_id', $account->getKey())
            ->update(['is_default' => false]);

        $cardToken = Arr::get($response, 'Data.CardToken') ?? Arr::get($response, 'Data.Token');

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

        $token = OfficeGuyToken::createFromApiResponse($account, $response);
        $token->update(['is_default' => true]);

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

    private function createPermanentTokenFromSingleUseToken(string $singleUseToken): array
    {
        $connector = new SumitConnector;
        $request = new CreateTokenRequest(
            token: TokenData::fromSingleUseToken(
                singleUseToken: $singleUseToken,
                paramJ: (string) config('officeguy.token_param', '5'),
            ),
            credentials: $this->credentials(),
        );

        $response = $connector->send($request)->json();
        $status = Arr::get($response, 'Status');
        $success = Arr::get($response, 'Data.Success', false);

        if ($status !== 0 || $success !== true) {
            $message = Arr::get($response, 'UserErrorMessage')
                ?? Arr::get($response, 'Data.ResultDescription')
                ?? 'Failed to tokenize the payment method with SUMIT.';

            throw new \RuntimeException((string) $message);
        }

        return $response;
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
