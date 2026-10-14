<?php

namespace App\Http\Resources;

use App\Media\ImageStore;
use App\Models\Barber;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Barber
 */
class BarberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->display_name,
            'bio' => $this->bio,
            'avatar_url' => app(ImageStore::class)->url($this->avatar_path),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            /** Whether this profile belongs to the signed-in user. */
            'is_me' => $this->user_id !== null && $this->user_id === $request->user()?->id,
            'user' => $this->whenLoaded('user', fn () => $this->user === null ? null : [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            /** Services this barber offers. Null price or duration means the service default. */
            'services' => $this->whenLoaded('services', fn () => $this->services->map(fn (Service $service): array => [
                'service_id' => $service->id,
                /** @var int|null */
                'price_ore' => $service->getRelationValue('pivot')?->getAttribute('price_ore'),
                /** @var int|null */
                'duration_minutes' => $service->getRelationValue('pivot')?->getAttribute('duration_minutes'),
            ])->values()),
        ];
    }
}
