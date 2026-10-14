<?php

namespace App\Models;

use App\Enums\TimeOffReason;
use App\Models\Concerns\BelongsToShop;
use Database\Factories\TimeOffFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property int $barber_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property TimeOffReason $reason
 * @property string|null $note
 */
#[Table('time_off')]
#[Fillable(['barber_id', 'starts_at', 'ends_at', 'reason', 'note'])]
class TimeOff extends Model
{
    use BelongsToShop;

    /** @use HasFactory<TimeOffFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reason' => TimeOffReason::class,
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
