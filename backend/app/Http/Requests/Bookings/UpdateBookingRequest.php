<?php

namespace App\Http\Requests\Bookings;

use App\Rules\IsoDateTimeRule;
use App\Tenancy\CurrentShop;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** Move the booking to this time. */
            'starts_at' => ['sometimes', 'string', new IsoDateTimeRule],
            /** Move the booking to another barber. */
            'barber_id' => ['sometimes', 'integer', Rule::exists('barbers', 'id')->where('shop_id', app(CurrentShop::class)->id())->whereNull('deleted_at')],
            'staff_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function startsAt(): ?CarbonImmutable
    {
        return $this->filled('starts_at') ? CarbonImmutable::parse($this->string('starts_at')->value())->utc() : null;
    }
}
