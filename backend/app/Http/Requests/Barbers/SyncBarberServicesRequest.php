<?php

namespace App\Http\Requests\Barbers;

use App\Tenancy\CurrentShop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The services a barber offers. A null price or duration means the service's default applies.
 */
class SyncBarberServicesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'services' => ['present', 'array'],
            'services.*.id' => [
                'required', 'integer', 'distinct',
                Rule::exists('services', 'id')
                    ->where('shop_id', app(CurrentShop::class)->id())
                    ->whereNull('deleted_at'),
            ],
            'services.*.price_ore' => ['nullable', 'integer', 'between:0,10000000'],
            'services.*.duration_minutes' => ['nullable', 'integer', 'between:5,480', 'multiple_of:5'],
        ];
    }

    /**
     * @return array<int, array{price_ore: int|null, duration_minutes: int|null}>
     */
    public function pivotData(): array
    {
        /** @var array<int, array{id: int, price_ore?: int|null, duration_minutes?: int|null}> $services */
        $services = $this->validated('services');

        return collect($services)->mapWithKeys(fn (array $service): array => [
            (int) $service['id'] => [
                'price_ore' => $service['price_ore'] ?? null,
                'duration_minutes' => $service['duration_minutes'] ?? null,
            ],
        ])->all();
    }
}
