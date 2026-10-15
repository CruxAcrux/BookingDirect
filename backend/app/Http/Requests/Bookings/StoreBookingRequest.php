<?php

namespace App\Http\Requests\Bookings;

use App\Rules\IsoDateTimeRule;
use App\Rules\PhoneNumberRule;
use App\Support\PhoneNumber;
use App\Tenancy\CurrentShop;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $shopId = app(CurrentShop::class)->id();

        return [
            'barber_id' => ['required', 'integer', Rule::exists('barbers', 'id')->where('shop_id', $shopId)->whereNull('deleted_at')],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('shop_id', $shopId)->whereNull('deleted_at')],
            'starts_at' => ['required', 'string', new IsoDateTimeRule],
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:120'],
            'customer.phone' => ['required', 'string', new PhoneNumberRule],
            'customer.email' => ['nullable', 'string', 'email', 'max:255'],
            'staff_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('starts_at')->value())->utc();
    }

    public function customerPhone(): string
    {
        return (string) PhoneNumber::normalize($this->string('customer.phone')->value());
    }
}
