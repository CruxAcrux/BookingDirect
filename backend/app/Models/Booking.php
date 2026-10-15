<?php

namespace App\Models;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\CancelledBy;
use App\Models\Concerns\BelongsToShop;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $shop_id
 * @property int $barber_id
 * @property int $customer_id
 * @property int $service_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property Carbon $blocked_until
 * @property BookingStatus $status
 * @property BookingSource $source
 * @property int $price_ore
 * @property int $duration_minutes
 * @property int $buffer_minutes
 * @property string|null $customer_note
 * @property string|null $staff_note
 * @property string $manage_token_hash
 * @property Carbon|null $cancelled_at
 * @property CancelledBy|null $cancelled_by
 * @property string|null $cancellation_message
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Barber $barber
 * @property-read Customer $customer
 * @property-read Service $service
 */
#[Fillable([
    'barber_id', 'customer_id', 'service_id', 'starts_at', 'ends_at', 'blocked_until',
    'status', 'source', 'price_ore', 'duration_minutes', 'buffer_minutes',
    'customer_note', 'staff_note',
])]
#[Hidden(['manage_token_hash', 'during'])]
class Booking extends Model
{
    use BelongsToShop;

    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $attributes = [
        'customer_note' => null,
        'staff_note' => null,
        'cancelled_at' => null,
        'cancelled_by' => null,
        'cancellation_message' => null,
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'blocked_until' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => BookingStatus::class,
            'source' => BookingSource::class,
            'cancelled_by' => CancelledBy::class,
        ];
    }

    /**
     * Creates a new manage-link token. Only its hash is stored, so the plain token is
     * returned once and must be sent to the customer right away.
     */
    public function issueManageToken(): string
    {
        $token = Str::random(40);
        $this->manage_token_hash = self::hashToken($token);

        return $token;
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeBlockingTime(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::blockingValues());
    }

    /**
     * @return BelongsTo<Barber, $this>
     */
    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }
}
