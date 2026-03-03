<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class InitiateCheckoutRequest extends FormRequest
{
    /** Keys that must never be sent to checkout (PCI: server must not receive card data). */
    private const FORBIDDEN_CARD_KEYS = [
        'card_number', 'cardnumber', 'cc_number', 'ccnumber',
        'cvv', 'cvc', 'ccv', 'cid', 'csc',
        'expiration', 'expiry', 'exp_month', 'exp_year', 'expyear',
        'credit_card', 'creditcard',
        'og_ccnum', 'og_ccv', 'og_expmonth', 'og_expyear',
        'og-ccnum', 'og-ccv', 'og-expmonth', 'og-expyear',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $keys = array_map('strtolower', array_keys($this->all()));
        foreach ($keys as $key) {
            foreach (self::FORBIDDEN_CARD_KEYS as $forbidden) {
                $forbidden = strtolower($forbidden);
                if ($key === $forbidden || str_contains($key, $forbidden)) {
                    throw ValidationException::withMessages([
                        'body' => ['Card data must not be sent to the server. Use tokenization only.'],
                    ])->status(400);
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'exists:plans,id'],
            'token' => ['nullable', 'string', 'min:1'],
        ];
    }
}
