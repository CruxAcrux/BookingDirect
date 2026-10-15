<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Database\Factories\PortfolioImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $shop_id
 * @property int $barber_id
 * @property string $path
 * @property string|null $thumbnail_path
 * @property string|null $caption
 * @property int $sort_order
 */
#[Fillable(['barber_id', 'path', 'thumbnail_path', 'caption', 'sort_order'])]
class PortfolioImage extends Model
{
    use BelongsToShop;

    /** @use HasFactory<PortfolioImageFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Barber, $this>
     */
    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }
}
