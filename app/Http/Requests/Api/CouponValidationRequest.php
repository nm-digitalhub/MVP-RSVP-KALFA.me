<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CouponValidationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64'],
            'plan_id' => ['nullable', 'integer', 'exists:product_plans,id'],
            'amount_minor' => ['required', 'integer', 'min:0'],
        ];
    }
}
