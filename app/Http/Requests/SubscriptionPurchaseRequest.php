<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'min:1'],
            'payment_token' => ['required', 'string', 'min:1'],
            'coupon_code' => ['nullable', 'string', 'max:64'],
        ];
    }
}
