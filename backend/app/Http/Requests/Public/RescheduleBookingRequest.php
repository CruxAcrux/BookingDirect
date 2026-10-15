<?php

namespace App\Http\Requests\Public;

use App\Rules\IsoDateTimeRule;
use App\Tenancy\CurrentShop;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RescheduleBookingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'string', new IsoDateTimeRule],
            /** Leave out to keep any barber who is free at the new time. */
            'barber_id' => ['nullable', 'integer', Rule::exists('barbers', 'id')->where('shop_id', app(CurrentShop::class)->id())->where('is_active', true)->whereNull('deleted_at')],
        ];
    }

    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('starts_at')->value())->utc();
    }
}
