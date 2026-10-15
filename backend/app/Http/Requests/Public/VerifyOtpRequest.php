<?php

namespace App\Http\Requests\Public;

class VerifyOtpRequest extends RequestOtpRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'code' => ['required', 'string', 'digits:6'],
        ];
    }
}
