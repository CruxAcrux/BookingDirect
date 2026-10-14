<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShopRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('shops', 'slug')],
            'timezone' => ['sometimes', 'string', 'timezone:all'],
            'locale' => ['sometimes', Rule::in(['sv', 'en'])],
            'owner' => ['required', 'array'],
            'owner.name' => ['required', 'string', 'max:120'],
            'owner.email' => ['required', 'string', 'email', 'max:255'],
        ];
    }
}
