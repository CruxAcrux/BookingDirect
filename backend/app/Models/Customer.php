<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Database\Factories\CustomerFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property string|null $name
 * @property string $phone E.164, e.g. +46701234567
 * @property string|null $email
 * @property string $locale
 * @property string|null $notes
 * @property Carbon|null $marketing_consent_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'phone', 'email', 'locale', 'notes', 'marketing_consent_at'])]
/**
 * Customers sign in with a one-time SMS code, so they have no password or remember token.
 */
class Customer extends Model implements AuthenticatableContract, HasLocalePreference
{
    use Authenticatable;
    use BelongsToShop;

    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    use SoftDeletes;

    /** Mirrors the column defaults so freshly created models are complete. */
    protected $attributes = [
        'name' => null,
        'email' => null,
        'locale' => 'sv',
        'notes' => null,
        'marketing_consent_at' => null,
    ];

    protected function casts(): array
    {
        return [
            'marketing_consent_at' => 'datetime',
        ];
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function preferredLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
