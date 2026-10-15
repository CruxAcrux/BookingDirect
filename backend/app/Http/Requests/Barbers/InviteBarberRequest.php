<?php

namespace App\Http\Requests\Barbers;

use Illuminate\Foundation\Http\FormRequest;

class InviteBarberRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'name' => ['sometimes', 'nullable', 'string', 'max:120'],
        ];
    }
}
