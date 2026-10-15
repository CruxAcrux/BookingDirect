<?php

namespace App\Http\Resources;

use App\Media\ImageStore;
use App\Models\Barber;
use App\Models\OpeningHour;
use App\Models\PortfolioImage;
use App\Models\Service;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Everything the public booking page needs about a shop. Only active services and
 * barbers are included.
 *
 * @mixin Shop
 */
class PublicShopResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $images = app(ImageStore::class);

        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            /** @var string|null */
            'logo_url' => $images->url($this->logo_path),
            /** @var string|null */
            'cover_url' => $images->url($this->cover_path),
            'max_days_ahead' => $this->max_days_ahead,
            'cancellation_window_hours' => $this->cancellation_window_hours,
            'walk_ins_enabled' => $this->walk_ins_enabled,
            'opening_hours' => WeeklyHourResource::collection($this->openingHours->sortBy(fn (OpeningHour $hour) => [$hour->weekday->value, $hour->opens_at])->values()),
            'services' => $this->services->map(fn (Service $service): array => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration_minutes' => $service->duration_minutes,
                'price_ore' => $service->price_ore,
            ])->values(),
            'barbers' => $this->barbers->map(fn (Barber $barber): array => [
                'id' => $barber->id,
                'display_name' => $barber->display_name,
                'bio' => $barber->bio,
                /** @var string|null */
                'avatar_url' => $images->url($barber->avatar_path),
                /** Services this barber offers, with their own price and duration. */
                'services' => $barber->services->map(fn (Service $service): array => [
                    'service_id' => $service->id,
                    /** @var int */
                    'price_ore' => $service->getRelationValue('pivot')?->getAttribute('price_ore') ?? $service->price_ore,
                    /** @var int */
                    'duration_minutes' => $service->getRelationValue('pivot')?->getAttribute('duration_minutes') ?? $service->duration_minutes,
                ])->values(),
                'portfolio' => $barber->portfolioImages->map(fn (PortfolioImage $image): array => [
                    'id' => $image->id,
                    /** @var string */
                    'url' => $images->url($image->path),
                    /** @var string */
                    'thumbnail_url' => $images->url($image->thumbnail_path ?? $image->path),
                    'caption' => $image->caption,
                ])->values(),
            ])->values(),
        ];
    }
}
