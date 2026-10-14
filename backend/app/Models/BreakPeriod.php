<?php

namespace App\Models;

use App\Enums\Weekday;
use App\Models\Concerns\BelongsToShop;
use Database\Factories\BreakPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
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
#[Table('breaks')]
#[Fillable(['barber_id', 'weekday', 'starts_at', 'ends_at'])]
class BreakPeriod extends Model
{
    use BelongsToShop;

    /** @use HasFactory<BreakPeriodFactory> */
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
