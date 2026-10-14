<?php

namespace App\Http\Requests\Barbers;

use App\Enums\TimeOffReason;
use App\Tenancy\CurrentShop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Times are local wall-clock times in the shop's timezone, e.g. "2026-11-03T09:00".
 * A full day off runs from 00:00 to 00:00 the next day.
 */
class TimeOffRequest extends FormRequest
{
    private const FORMAT = 'Y-m-d\TH:i';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'starts_at' => [$required, 'date_format:'.self::FORMAT],
            'ends_at' => [$required, 'date_format:'.self::FORMAT, 'after:starts_at'],
            'reason' => [$required, Rule::enum(TimeOffReason::class)],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        $timezone = app(CurrentShop::class)->getOrFail()->timezone;
        $data = $this->validated();

        foreach (['starts_at', 'ends_at'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = Carbon::createFromFormat(self::FORMAT, $data[$key], $timezone)?->utc();
            }
        }

        return $data;
    }
}
