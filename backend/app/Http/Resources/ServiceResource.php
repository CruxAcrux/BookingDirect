<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Service
 */
class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'buffer_minutes' => $this->buffer_minutes,
            /** Price in öre. */
            'price_ore' => $this->price_ore,
            'rebook_after_days' => $this->rebook_after_days,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
