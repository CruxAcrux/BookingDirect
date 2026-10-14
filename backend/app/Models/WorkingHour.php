<?php

namespace App\Models;

use App\Enums\Weekday;
use App\Models\Concerns\BelongsToShop;
use Database\Factories\WorkingHourFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Times are local wall-clock times (HH:MM:SS) in the shop's timezone.
 *
 * @property int $id
 * @property int $shop_id
 * @property int $barber_id
 * @property Weekday $weekday
 * @property string $starts_at
 * @property string $ends_at
 */
#[Fillable(['barber_id', 'weekday', 'starts_at', 'ends_at'])]
class WorkingHour extends Model
{
    use BelongsToShop;

    /** @use HasFactory<WorkingHourFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'weekday' => Weekday::class,
        ];
    }

    /**
     * @return BelongsTo<Barber, $this>
     */
    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }
}
