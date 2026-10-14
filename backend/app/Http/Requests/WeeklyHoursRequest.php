<?php

namespace App\Http\Requests;

use App\Enums\Weekday;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

/**
 * A full week of time ranges: opening hours, working hours or breaks. Several ranges per day are allowed
 * (closed for lunch), but they may not overlap. Days without ranges are closed.
 */
class WeeklyHoursRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hours' => ['present', 'array', 'max:50'],
            'hours.*.weekday' => ['required', 'integer', 'between:1,7'],
            'hours.*.starts_at' => ['required', 'date_format:H:i'],
            'hours.*.ends_at' => ['required', 'date_format:H:i', 'after:hours.*.starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['hours.*.ends_at.after' => __('rules.time_range')];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach ($this->ranges()->groupBy(fn (array $range): int => $range['weekday']->value) as $weekday => $ranges) {
                $previousEnd = null;

                foreach ($ranges->sortBy('start') as $range) {
                    if ($previousEnd !== null && $range['start'] < $previousEnd) {
                        $validator->errors()->add('hours', __('rules.overlap', ['day' => $this->dayName($weekday)]));
                        break;
                    }

                    $previousEnd = $range['end'];
                }
            }
        }];
    }

    /**
     * @return Collection<int, array{weekday: Weekday, start: string, end: string}>
     */
    public function ranges(): Collection
    {
        /** @var array<int, array<string, mixed>> $hours */
        $hours = $this->input('hours', []);

        return collect($hours)->map(fn (array $range): array => [
            'weekday' => Weekday::from((int) $range['weekday']),
            'start' => (string) $range['starts_at'],
            'end' => (string) $range['ends_at'],
        ]);
    }

    private function dayName(int $weekday): string
    {
        $date = Carbon::now()->startOfWeek()->addDays($weekday - 1);
        $date->setLocale(app()->getLocale());

        return $date->isoFormat('dddd');
    }
}
