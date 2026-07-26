<?php

namespace App\Http\Traits;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Image, File;
use Carbon\Carbon;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

trait ImageAttributes
{
    public function getImageAttribute(){
        if (isset($this->attributes['image']) && !filter_var($this->attributes['image'], FILTER_VALIDATE_URL) === false) 
        {
            return $this->attributes['image'];
        } else {
            return isset($this->imgFolder) && !empty($this->attributes['image']) 
                    && Storage::disk('s3')->exists($this->imgFolder.'/'.$this->attributes['image']) 
                   ? self::getImg($this->imgFolder, $this->attributes['image']) 
                   : asset('/assets/img/'.$this->avatar);
        }
    }

    public function setImageAttribute($value){
        if (!empty($value)){
            $old_Image = (isset($this->image) && !empty($this->image)) ? substr(strrchr($this->image, '/'), 1) : '';
            
            if (!empty($old_Image) && Storage::disk('s3')->exists($this->imgFolder.'/'.$old_Image)) {
                Storage::disk('s3')->delete($this->imgFolder.'/'.$old_Image);
            }

            $filename = $this->generateImageName($value);
            $this->resizeImage($this->imgFolder, $value, $filename);

            $this->attributes['image'] = $filename;
        }
    }

    static function getImg($imageFolder, $filename){
        return (!empty($filename)) 
            ? Storage::disk('s3')->url($imageFolder.'/'.$filename) 
            : asset('/assets/img/'.$this->avatar);
    }

    function generateImageName($file){
        $fileNameWithExt = $file->getClientOriginalName();
        $filename = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
        $extention = strtolower($file->getClientOriginalExtension());
        $fileNameToStore = $filename.'_'.time().'.'.$extention;
        return Str::snake($fileNameToStore);
    }

    public function resizeImage($path, $photo, $filename)
    {
        $manager = new ImageManager(new Driver());

        $width  = $this->imageWidth  ?? 1000;
        $height = $this->imageHeight ?? 1000;

        $image = $manager->read($photo);

        if ($image->width() >= $width && $image->height() >= $height) {
            $image->cover($width, $height);
        }

        $encodedImage = (string) $image->encodeByExtension(
            pathinfo($filename, PATHINFO_EXTENSION), 
            quality: 80
        );

        Storage::disk('s3')->put($path.'/'.$filename, $encodedImage, 'public');

        return $filename;
    }
}