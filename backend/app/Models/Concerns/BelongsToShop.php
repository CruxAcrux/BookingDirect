<?php

namespace App\Models\Concerns;

use App\Models\Shop;
use App\Tenancy\CurrentShop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Scopes a model to the current shop and fills in `shop_id` on create.
 *
 * Queries are only filtered once a shop has been resolved, so console commands,
 * seeders and platform-admin code can still work across shops on purpose.
 *
 * @mixin Model
 */
trait BelongsToShop
{
    public static function bootBelongsToShop(): void
    {
        static::addGlobalScope('shop', function (Builder $query): void {
            $shopId = app(CurrentShop::class)->id();

            if ($shopId !== null) {
                $query->where($query->qualifyColumn('shop_id'), $shopId);
            }
        });

        static::creating(function (Model $model): void {
            $currentShopId = app(CurrentShop::class)->id();

            if ($currentShopId === null) {
                return;
            }

            $shopId = $model->getAttribute('shop_id');

            if ($shopId !== null && (int) $shopId !== $currentShopId) {
                throw new LogicException('Cannot create a record for another shop than the current one.');
            }

            $model->setAttribute('shop_id', $currentShopId);
        });
    }

    /**
     * @return BelongsTo<Shop, $this>
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
