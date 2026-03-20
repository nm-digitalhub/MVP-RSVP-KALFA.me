<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeatAssignmentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'assignments' => ['required', 'array'],
            'assignments.*.guest_id' => ['required', 'exists:guests,id'],
            'assignments.*.event_table_id' => ['required', 'exists:event_tables,id'],
            'assignments.*.seat_number' => ['nullable', 'string', 'max:50'],
        ];
    }
}
