<?php

namespace App\Http\Requests\Public;

use App\Tenancy\CurrentShop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvailabilityRequest extends FormRequest
{
    public const MAX_DAYS = 14;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $shopId = app(CurrentShop::class)->id();

        return [
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('shop_id', $shopId)->where('is_active', true)->whereNull('deleted_at')],
            /** Leave out for "first available": every barber who offers the service. */
            'barber_id' => ['nullable', 'integer', Rule::exists('barbers', 'id')->where('shop_id', $shopId)->where('is_active', true)->whereNull('deleted_at')],
            /** First local date, e.g. 2026-11-03. */
            'from' => ['required', 'date_format:Y-m-d'],
            /** Last local date, at most 14 days after `from`. */
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:'.$this->maxTo()],
        ];
    }

    private function maxTo(): string
    {
        $from = $this->string('from')->value();

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) === 1
            ? date('Y-m-d', (int) strtotime($from.' +'.(self::MAX_DAYS - 1).' days'))
            : '9999-12-31';
    }
}
