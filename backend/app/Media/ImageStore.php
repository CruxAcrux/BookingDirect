<?php

namespace App\Media;

use Illuminate\Image\Image;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Resizes uploads, re-encodes them as WebP and stores them on the public disk.
 *
 * Re-encoding also strips EXIF data (such as GPS position) from photos.
 */
class ImageStore
{
    private const QUALITY = 82;

    /**
     * @return string the stored path on the public disk
     */
    public function store(Image $image, ImagePreset $preset): string
    {
        $path = $this->resize($image, $preset)
            ->optimize('webp', self::QUALITY)
            ->storePublicly($preset->directory(), 'public');

        if ($path === false) {
            throw new RuntimeException("Could not store the {$preset->name} image.");
        }

        return $path;
    }

    /**
     * Stores the full image and a square thumbnail from a single upload.
     *
     * @return array{path: string, thumbnail_path: string}
     */
    public function storeWithThumbnail(Image $image): array
    {
        return [
            'path' => $this->store($image, ImagePreset::Portfolio),
            'thumbnail_path' => $this->store($image, ImagePreset::PortfolioThumbnail),
        ];
    }

    public function delete(?string ...$paths): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }
    }

    public function url(?string $path): ?string
    {
        return $path === null ? null : Storage::disk('public')->url($path);
    }

    private function resize(Image $image, ImagePreset $preset): Image
    {
        // Images are immutable: every call returns a transformed copy.
        return match ($preset) {
            ImagePreset::Avatar => $image->cover(400, 400),
            ImagePreset::Logo => $image->scale(512, 512),
            ImagePreset::Cover => $image->scale(width: 2000),
            ImagePreset::Portfolio => $image->scale(1600, 1600),
            ImagePreset::PortfolioThumbnail => $image->cover(600, 600),
        };
    }
}
