<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A one-time sign-in code sent by SMS. Only a keyed hash of the code is stored.
 *
 * @property int $id
 * @property int $shop_id
 * @property string $phone
 * @property string $code_hash
 * @property int $attempts
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 * @property Carbon $created_at
 */
#[Fillable(['phone', 'code_hash', 'expires_at'])]
class CustomerOtp extends Model
{
    use BelongsToShop;

    protected $attributes = [
        'attempts' => 0,
        'consumed_at' => null,
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public static function hashCode(string $phone, string $code): string
    {
        return hash_hmac('sha256', "{$phone}|{$code}", (string) config('app.key'));
    }
}
