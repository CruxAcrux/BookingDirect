<?php

namespace App\Http\Resources;

use App\Media\ImageStore;
use App\Models\PortfolioImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PortfolioImage
 */
class PortfolioImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $images = app(ImageStore::class);

        return [
            'id' => $this->id,
            'url' => $images->url($this->path),
            'thumbnail_url' => $images->url($this->thumbnail_path ?? $this->path),
            'caption' => $this->caption,
            'sort_order' => $this->sort_order,
        ];
    }
}
