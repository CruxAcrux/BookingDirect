<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Database\Factories\BarberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property int|null $user_id
 * @property string $display_name
 * @property string|null $bio
 * @property string|null $avatar_path
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['user_id', 'display_name', 'bio', 'is_active', 'sort_order'])]
class Barber extends Model
{
    use BelongsToShop;

    /** @use HasFactory<BarberFactory> */
    use HasFactory;

    use SoftDeletes;

    /** Mirrors the column defaults so freshly created models are complete. */
    protected $attributes = [
        'bio' => null,
        'avatar_path' => null,
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Price and duration on the pivot override the service's defaults when set.
     *
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withPivot('price_ore', 'duration_minutes');
    }

    /**
     * @return HasMany<PortfolioImage, $this>
     */
    public function portfolioImages(): HasMany
    {
        return $this->hasMany(PortfolioImage::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<WorkingHour, $this>
     */
    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class);
    }

    /**
     * @return HasMany<BreakPeriod, $this>
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(BreakPeriod::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<TimeOff, $this>
     */
    public function timeOffs(): HasMany
    {
        return $this->hasMany(TimeOff::class);
    }
}
