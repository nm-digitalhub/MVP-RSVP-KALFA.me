<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\Accounts\StoreAccountPaymentMethodRequest;
use App\Models\Account;
use App\Services\Sumit\AccountPaymentMethodManager;
use App\Services\SystemAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use OfficeGuy\LaravelSumitGateway\Models\OfficeGuyToken;

final class AccountPaymentMethodController extends Controller
{
    public function store(
        StoreAccountPaymentMethodRequest $request,
        Account $account,
        AccountPaymentMethodManager $paymentMethodManager,
    ): RedirectResponse {
        try {
            $token = $paymentMethodManager->storeSingleUseToken($account, $request->validated('og-token'));

            SystemAuditLogger::log($request->user(), 'account.payment_method_added', $account, [
                'officeguy_token_id' => $token->id,
                'last_four' => $token->last_four,
                'card_type' => $token->card_type,
                'is_default' => $token->is_default,
            ]);

            return redirect()
                ->to(route('system.accounts.show', $account).'#billing-methods')
                ->with('success', __('Payment method added and set as default.'));
        } catch (\RuntimeException $exception) {
            return redirect()
                ->to(route('system.accounts.show', $account).'#billing-methods')
                ->with('error', $exception->getMessage());
        }
    }

    public function setDefault(Request $request,
        Account $account,
        OfficeGuyToken $paymentMethod,
        AccountPaymentMethodManager $paymentMethodManager,
    ): RedirectResponse {
        try {
            $paymentMethodManager->setDefault($account, $paymentMethod);

            SystemAuditLogger::log($request->user(), 'account.payment_method_default_updated', $account, [
                'officeguy_token_id' => $paymentMethod->id,
                'last_four' => $paymentMethod->last_four,
            ]);

            return redirect()
                ->to(route('system.accounts.show', $account).'#billing-methods')
                ->with('success', __('Default payment method updated successfully.'));
        } catch (\RuntimeException $exception) {
            return redirect()
                ->to(route('system.accounts.show', $account).'#billing-methods')
                ->with('error', $exception->getMessage());
        }
    }

    public function destroy(Request $request,
        Account $account,
        OfficeGuyToken $paymentMethod,
        AccountPaymentMethodManager $paymentMethodManager,
    ): RedirectResponse {
        try {
            $paymentMethodManager->delete($account, $paymentMethod);

            SystemAuditLogger::log($request->user(), 'account.payment_method_deleted', $account, [
                'officeguy_token_id' => $paymentMethod->id,
                'last_four' => $paymentMethod->last_four,
            ]);

            return redirect()
                ->to(route('system.accounts.show', $account).'#billing-methods')
                ->with('success', __('Payment method removed successfully.'));
        } catch (\RuntimeException $exception) {
            return redirect()
                ->to(route('system.accounts.show', $account).'#billing-methods')
                ->with('error', $exception->getMessage());
        }
    }
}
