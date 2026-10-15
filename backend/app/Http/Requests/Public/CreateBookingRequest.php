<?php

namespace App\Http\Requests\Public;

use App\Rules\IsoDateTimeRule;
use App\Tenancy\CurrentShop;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBookingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $shopId = app(CurrentShop::class)->id();

        return [
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('shop_id', $shopId)->where('is_active', true)->whereNull('deleted_at')],
            /** Leave out to book whoever is free first at that time. */
            'barber_id' => ['nullable', 'integer', Rule::exists('barbers', 'id')->where('shop_id', $shopId)->where('is_active', true)->whereNull('deleted_at')],
            /** A start time from the availability endpoint, e.g. 2026-11-03T08:15:00Z. */
            'starts_at' => ['required', 'string', new IsoDateTimeRule],
            /** Required the first time a customer books. */
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('starts_at')->value())->utc();
    }
}
