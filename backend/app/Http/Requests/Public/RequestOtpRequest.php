<?php

namespace App\Http\Requests\Public;

use App\Rules\PhoneNumberRule;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** Swedish numbers without a country code are accepted, e.g. 070-123 45 67. */
            'phone' => ['required', 'string', new PhoneNumberRule],
        ];
    }

    public function phone(): string
    {
        return (string) PhoneNumber::normalize($this->string('phone')->value());
    }
}
