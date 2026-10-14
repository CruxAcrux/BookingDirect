<?php

namespace App\Models;

use App\Enums\Weekday;
use App\Models\Concerns\BelongsToShop;
use Database\Factories\OpeningHourFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Times are local wall-clock times (HH:MM:SS) in the shop's timezone.
 *
 * @property int $id
 * @property int $shop_id
 * @property Weekday $weekday
 * @property string $opens_at
 * @property string $closes_at
 */
#[Fillable(['weekday', 'opens_at', 'closes_at'])]
class OpeningHour extends Model
{
    use BelongsToShop;

    /** @use HasFactory<OpeningHourFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'weekday' => Weekday::class,
        ];
    }
}
