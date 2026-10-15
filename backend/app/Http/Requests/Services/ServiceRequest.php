<?php

namespace App\Http\Requests\Services;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Prices are in öre (1 kr = 100 öre). Durations are in whole minutes, in steps of 5.
 */
class ServiceRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'duration_minutes' => [$required, 'integer', 'between:5,480', 'multiple_of:5'],
            'buffer_minutes' => ['sometimes', 'integer', 'between:0,120', 'multiple_of:5'],
            'price_ore' => [$required, 'integer', 'between:0,10000000'],
            'rebook_after_days' => ['sometimes', 'nullable', 'integer', 'between:1,365'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
