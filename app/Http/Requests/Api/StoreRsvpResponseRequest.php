<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreRsvpResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'response' => ['required', 'string', 'in:yes,no,maybe'],
            'attendees_count' => ['nullable', 'integer', 'min:0'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
