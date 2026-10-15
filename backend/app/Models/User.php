<?php

namespace App\Models;

use App\Enums\ShopRole;
use App\Notifications\Auth\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $locale
 * @property bool $is_platform_admin
 * @property Carbon|null $email_verified_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ShopMembership $membership
 */
#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** Mirrors the column defaults so freshly created models are complete. */
    protected $attributes = [
        'locale' => 'sv',
        'is_platform_admin' => false,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Shop, $this, ShopMembership, 'membership'>
     */
    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class)
            ->using(ShopMembership::class)
            ->as('membership')
            ->withPivot('id', 'role')
            ->withTimestamps();
    }

    public function roleIn(Shop $shop): ?ShopRole
    {
        $shop = $this->shops->firstWhere('id', $shop->id);

        return $shop?->membership->role;
    }

    /** The user's barber profile in the current shop, if they cut hair there. */
    public function barberProfileId(): ?int
    {
        $id = Barber::where('user_id', $this->id)->value('id');

        return is_numeric($id) ? (int) $id : null;
    }

    public function isOwnerOf(Shop $shop): bool
    {
        return $this->roleIn($shop) === ShopRole::Owner;
    }

    /**
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function preferredLocale(): string
    {
        return $this->locale;
    }
}
