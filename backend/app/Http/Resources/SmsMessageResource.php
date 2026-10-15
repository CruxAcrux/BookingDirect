<?php

namespace App\Http\Resources;

use App\Models\Booking;
use App\Models\SmsMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SmsMessage
 */
class SmsMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'to' => $this->to,
            'body' => $this->body,
            'type' => $this->type,
            'sent_at' => $this->sent_at,
            /** @var int|null */
            'booking_id' => $this->related_type === (new Booking)->getMorphClass() ? $this->related_id : null,
        ];
    }
}
