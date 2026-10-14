<?php

namespace App\Http\Resources;

use App\Media\ImageStore;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The current shop's profile and booking rules, as staff see them.
 *
 * @mixin Shop
 */
class ShopResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $images = app(ImageStore::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'logo_url' => $images->url($this->logo_path),
            'cover_url' => $images->url($this->cover_path),
            'min_notice_minutes' => $this->min_notice_minutes,
            'max_days_ahead' => $this->max_days_ahead,
            'cancellation_window_hours' => $this->cancellation_window_hours,
            'slot_interval_minutes' => $this->slot_interval_minutes,
            'walk_ins_enabled' => $this->walk_ins_enabled,
        ];
    }
}
