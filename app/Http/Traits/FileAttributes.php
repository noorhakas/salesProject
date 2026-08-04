<?php

namespace App\Http\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use File;

trait FileAttributes
{
    /**
     * Get file URL
     */
    public function getFileAttribute()
    {
        if (isset($this->attributes['file']) && filter_var($this->attributes['file'], FILTER_VALIDATE_URL)) {
            return $this->attributes['file'];
        }

        return isset($this->imgFolder) && !empty($this->attributes['file'])
            && Storage::disk('s3')->exists($this->imgFolder.'/'.$this->attributes['file'])
            ? self::getFile($this->imgFolder, $this->attributes['file'])
            : '';
    }


    /**
     * Upload file
     */
    public function setFileAttribute($value)
    {
        if (!empty($value)) {

            // Delete old file
            $oldFile = (isset($this->attributes['file']) && !empty($this->attributes['file']))
                ? $this->attributes['file']
                : '';

            if (!empty($oldFile) && Storage::disk('s3')->exists($this->imgFolder.'/'.$oldFile)) {
                Storage::disk('s3')->delete($this->imgFolder.'/'.$oldFile);
            }


            // Generate new file name
            $filename = $this->generateFileName($value);


            // Upload to S3 / R2
            Storage::disk('s3')->putFileAs(
                $this->imgFolder,
                $value,
                $filename,
                'public'
            );


            // Save only filename
            $this->attributes['file'] = $filename;
        }
    }


    /**
     * Return file URL
     */
    static function getFile($folder, $filename)
    {
        return !empty($filename)
            ? Storage::disk('s3')->url($folder.'/'.$filename)
            : '';
    }


    /**
     * Generate filename
     */
    function generateFileName($file)
    {
        $fileNameWithExt = $file->getClientOriginalName();

        $filename = pathinfo($fileNameWithExt, PATHINFO_FILENAME);

        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        return Str::snake(
            $filename.'_'.time().'.'.$extension
        );
    }
}