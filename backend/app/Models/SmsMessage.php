<?php

namespace App\Models;

use App\Enums\SmsType;
use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property string $to
 * @property string $body
 * @property SmsType $type
 * @property string|null $related_type
 * @property int|null $related_id
 * @property Carbon $sent_at
 */
#[Fillable(['to', 'body', 'type', 'sent_at'])]
class SmsMessage extends Model
{
    use BelongsToShop;

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'type' => SmsType::class,
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
