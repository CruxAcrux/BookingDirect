<?php

namespace App\Media;

/**
 * How each kind of upload is resized before it is stored.
 */
enum ImagePreset: string
{
    case Avatar = 'avatars';
    case Logo = 'logos';
    case Cover = 'covers';
    case Portfolio = 'portfolio';
    case PortfolioThumbnail = 'portfolio/thumbnails';

    public function directory(): string
    {
        return $this->value;
    }
}
