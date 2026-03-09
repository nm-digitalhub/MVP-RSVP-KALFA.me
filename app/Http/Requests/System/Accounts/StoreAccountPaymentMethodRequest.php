<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Accounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;

final class StoreAccountPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_system_admin;
    }

    public function rules(): array
    {
        return [
            'og-token' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'og-token.required' => __('A SUMIT payment token is required before saving a payment method.'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        /** @var Route|null $route */
        $route = $this->route();
        $account = $route?->parameter('account');

        if ($account !== null) {
            return route('system.accounts.show', $account).'#billing-methods';
        }

        return parent::getRedirectUrl();
    }
}
