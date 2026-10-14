<?php

namespace App\Http\Requests\Shop;

use App\Rules\PhoneNumberRule;
use App\Support\PhoneNumber;
use App\Tenancy\CurrentShop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShopRequest extends FormRequest
{
    public const SLOT_INTERVALS = [5, 10, 15, 20, 30, 60];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'slug' => [
                'sometimes', 'required', 'string', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('shops', 'slug')->ignore(app(CurrentShop::class)->id()),
            ],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', new PhoneNumberRule],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
            'timezone' => ['sometimes', 'required', 'string', 'timezone:all'],
            'locale' => ['sometimes', 'required', Rule::in(['sv', 'en'])],
            'min_notice_minutes' => ['sometimes', 'required', 'integer', 'between:0,10080'],
            'max_days_ahead' => ['sometimes', 'required', 'integer', 'between:1,365'],
            'cancellation_window_hours' => ['sometimes', 'required', 'integer', 'between:0,168'],
            /** @var 5|10|15|20|30|60 */
            'slot_interval_minutes' => ['sometimes', 'required', 'integer', Rule::in(self::SLOT_INTERVALS)],
            'walk_ins_enabled' => ['sometimes', 'required', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $data = $this->validated();

        if (isset($data['phone']) && is_string($data['phone'])) {
            $data['phone'] = PhoneNumber::normalize($data['phone']);
        }

        return $data;
    }
}
