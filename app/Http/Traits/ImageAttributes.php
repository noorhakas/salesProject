<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

trait ImageAttributes
{
    /**
     * Get image URL.
     */
    public function getImageAttribute()
    {
        if (
            isset($this->attributes['image']) &&
            filter_var($this->attributes['image'], FILTER_VALIDATE_URL)
        ) {
            return $this->attributes['image'];
        }

        if (
            isset($this->imgFolder) &&
            !empty($this->attributes['image'])
        ) {
            $path = $this->imgFolder . '/' . $this->attributes['image'];

            if (Storage::exists($path)) {
                return Storage::url($path);
            }
        }

        return asset('/assets/img/' . $this->avatar);
    }

    /**
     * Save image.
     */
    public function setImageAttribute($value)
    {
        if (empty($value)) {
            return;
        }

        $filename = $this->generateImageName($value);

        // Delete old image
        if (!empty($this->attributes['image'])) {
            Storage::delete($this->imgFolder . '/' . $this->attributes['image']);
        }

        // Resize & upload
        $this->resizeImage($this->imgFolder, $value, $filename);

        $this->attributes['image'] = $filename;
    }

    /**
     * Generate image url.
     */
    public static function getImg($imageFolder, $filename)
    {
        return Storage::url($imageFolder . '/' . $filename);
    }

    /**
     * Generate unique filename.
     */
    public function generateImageName($file)
    {
        $filename = pathinfo(
            $file->getClientOriginalName(),
            PATHINFO_FILENAME
        );

        $extension = strtolower($file->getClientOriginalExtension());

        return Str::snake($filename . '_' . time() . '.' . $extension);
    }

    /**
     * Resize and upload image.
     */
    public function resizeImage($path, $photo, $filename)
    {
        $manager = new ImageManager(new Driver());

        $width  = $this->imageWidth ?? 1000;
        $height = $this->imageHeight ?? 1000;

        $image = $manager->read($photo);

        if ($image->width() >= $width && $image->height() >= $height) {
            $image->cover($width, $height);
        }

        Storage::put(
            $path . '/' . $filename,
            (string) $image->toWebp(80),
            [
                'visibility' => 'public',
            ]
        );

        return $filename;
    }
}