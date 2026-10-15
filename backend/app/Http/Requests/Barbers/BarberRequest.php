<?php

namespace App\Http\Requests\Barbers;

use Illuminate\Foundation\Http\FormRequest;

class BarberRequest extends FormRequest
{
    /** Fields a barber may change on their own profile. */
    public const PROFILE_FIELDS = ['display_name', 'bio'];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'display_name' => [$required, 'string', 'max:80'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
