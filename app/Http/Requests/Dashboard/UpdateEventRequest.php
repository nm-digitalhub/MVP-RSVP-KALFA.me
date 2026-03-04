<?php

declare(strict_types=1);

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'event_date' => ['nullable', 'date'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_address' => ['nullable', 'string', 'max:500'],
            'image' => [
                'nullable',
                File::image()
                    ->max(5 * 1024),
            ],
            'cropped_image' => ['nullable', 'string'],
            'remove_image' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
            'rsvp_welcome_message' => ['nullable', 'string', 'max:2000'],
            'program' => ['nullable', 'string', 'max:5000'],
            'custom' => ['nullable', 'array', 'max:10'],
            'custom.*.label' => ['nullable', 'string', 'max:100'],
            'custom.*.value' => ['nullable', 'string', 'max:500'],
        ];
    }
}
