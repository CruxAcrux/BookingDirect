<?php

namespace App\Models;

use App\Enums\ShopRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $shop_id
 * @property int $user_id
 * @property ShopRole $role
 */
class ShopMembership extends Pivot
{
    protected $table = 'shop_user';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'role' => ShopRole::class,
        ];
    }
}
