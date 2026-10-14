<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property string $name
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
class Customer extends Model
{
    use BelongsToShop;

    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    use SoftDeletes;

    /** Mirrors the column defaults so freshly created models are complete. */
    protected $attributes = [
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
}
