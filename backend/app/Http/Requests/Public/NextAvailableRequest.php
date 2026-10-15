<?php

namespace App\Http\Requests\Public;

use App\Tenancy\CurrentShop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NextAvailableRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $shopId = app(CurrentShop::class)->id();

        return [
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('shop_id', $shopId)->where('is_active', true)->whereNull('deleted_at')],
            'barber_id' => ['nullable', 'integer', Rule::exists('barbers', 'id')->where('shop_id', $shopId)->where('is_active', true)->whereNull('deleted_at')],
        ];
    }
}
