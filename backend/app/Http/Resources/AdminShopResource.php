<?php

namespace App\Http\Resources;

use App\Enums\ShopRole;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Shop
 */
class AdminShopResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'is_active' => $this->isActive(),
            'deactivated_at' => $this->deactivated_at,
            'created_at' => $this->created_at,
            'owners' => $this->whenLoaded('users', fn () => $this->users
                ->filter(fn (User $user): bool => $user->membership->role === ShopRole::Owner)
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])
                ->values()),
        ];
    }
}
