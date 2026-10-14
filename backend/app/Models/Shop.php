<?php

namespace App\Models;

use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string $timezone
 * @property string $locale
 * @property string|null $logo_path
 * @property string|null $cover_path
 * @property int $min_notice_minutes
 * @property int $max_days_ahead
 * @property int $cancellation_window_hours
 * @property int $slot_interval_minutes
 * @property bool $walk_ins_enabled
 * @property Carbon|null $deactivated_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ShopMembership $membership
 */
#[Fillable([
    'name', 'slug', 'address', 'phone', 'email', 'timezone', 'locale',
    'min_notice_minutes', 'max_days_ahead', 'cancellation_window_hours',
    'slot_interval_minutes', 'walk_ins_enabled',
])]
class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory;

    /** Mirrors the column defaults so freshly created models are complete. */
    protected $attributes = [
        'timezone' => 'Europe/Stockholm',
        'locale' => 'sv',
        'min_notice_minutes' => 60,
        'max_days_ahead' => 60,
        'cancellation_window_hours' => 24,
        'slot_interval_minutes' => 15,
        'walk_ins_enabled' => true,
        'deactivated_at' => null,
    ];

    protected function casts(): array
    {
        return [
            'walk_ins_enabled' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    /**
     * @return BelongsToMany<User, $this, ShopMembership, 'membership'>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(ShopMembership::class)
            ->as('membership')
            ->withPivot('id', 'role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Barber, $this>
     */
    public function barbers(): HasMany
    {
        return $this->hasMany(Barber::class);
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * @return HasMany<OpeningHour, $this>
     */
    public function openingHours(): HasMany
    {
        return $this->hasMany(OpeningHour::class);
    }

    /**
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
