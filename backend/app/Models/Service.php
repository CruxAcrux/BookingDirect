<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property string $name
 * @property string|null $description
 * @property int $duration_minutes
 * @property int $buffer_minutes
 * @property int $price_ore
 * @property int|null $rebook_after_days
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'name', 'description', 'duration_minutes', 'buffer_minutes', 'price_ore',
    'rebook_after_days', 'is_active', 'sort_order',
])]
class Service extends Model
{
    use BelongsToShop;

    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    use SoftDeletes;

    /** Mirrors the column defaults so freshly created models are complete. */
    protected $attributes = [
        'description' => null,
        'buffer_minutes' => 0,
        'rebook_after_days' => null,
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Barber, $this>
     */
    public function barbers(): BelongsToMany
    {
        return $this->belongsToMany(Barber::class)->withPivot('price_ore', 'duration_minutes');
    }
}
