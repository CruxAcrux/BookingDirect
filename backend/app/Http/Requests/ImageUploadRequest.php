<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Image\Image;
use Illuminate\Validation\Rules\File;

class ImageUploadRequest extends FormRequest
{
    public const MAX_KILOBYTES = 10 * 1024;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(self::MAX_KILOBYTES)],
            'caption' => ['sometimes', 'nullable', 'string', 'max:160'],
        ];
    }

    public function upload(): Image
    {
        /** @var Image */
        return $this->image('image');
    }
}
