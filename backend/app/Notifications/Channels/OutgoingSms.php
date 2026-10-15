<?php

namespace App\Notifications\Channels;

use App\Enums\SmsType;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Model;

final readonly class OutgoingSms
{
    public function __construct(
        public Shop $shop,
        public string $body,
        public SmsType $type,
        public ?Model $related = null,
    ) {}
}
