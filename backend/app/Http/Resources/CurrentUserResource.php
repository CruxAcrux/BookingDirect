<?php

namespace App\Http\Resources;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The signed-in staff member, with their shops and the shop they currently work in.
 *
 * @mixin User
 */
class CurrentUserResource extends JsonResource
{
    public function __construct(User $user, private readonly ?Shop $currentShop)
    {
        parent::__construct($user->loadMissing('shops'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'locale' => $this->locale,
            'is_platform_admin' => $this->is_platform_admin,
            /** @var int|null */
            'current_shop_id' => $this->currentShop?->id,
            'shops' => ShopMembershipResource::collection($this->shops),
        ];
    }
}
