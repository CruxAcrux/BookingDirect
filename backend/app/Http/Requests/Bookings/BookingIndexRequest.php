<?php

namespace App\Http\Requests\Bookings;

use Illuminate\Foundation\Http\FormRequest;

class BookingIndexRequest extends FormRequest
{
    public const MAX_DAYS = 42;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** First local date, e.g. 2026-11-02. */
            'from' => ['required', 'date_format:Y-m-d'],
            /** Last local date, at most 42 days after `from`. */
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'barber_id' => ['nullable', 'integer'],
            /** Include cancelled bookings. */
            'with_cancelled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function ($validator): void {
            $from = strtotime($this->string('from')->value());
            $to = strtotime($this->string('to')->value());

            if ($from !== false && $to !== false && ($to - $from) / 86400 >= self::MAX_DAYS) {
                $validator->errors()->add('to', __('validation.before_or_equal', ['attribute' => 'to', 'date' => self::MAX_DAYS.' days after from']));
            }
        }];
    }
}
