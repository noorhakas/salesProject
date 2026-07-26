<?php

namespace App\Http\Traits;
use Illuminate\Support\Str;
use Image, File;
use Carbon\Carbon;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

trait ImageAttributes
{
    /**
     * @return null|string
     */
    public function getImageAttribute(){
		if (isset($this->attributes['image']) && !filter_var($this->attributes['image'], FILTER_VALIDATE_URL) === false) 
		{
			return $this->attributes['image'];
		}else{
             return isset($this->imgFolder) && !empty($this->attributes['image']) && file_exists(public_path('storage/'.$this->imgFolder.'/'.$this->attributes['image'])) 
                   ? self::getImg($this->imgFolder,$this->attributes['image']) : asset('/assets/img/'.$this->avatar);
		}
    }


    /**
     * @param $value
     */
	public function setImageAttribute($value){
    $base_url = url('/');
    
   if (!empty($value)){
            // if(!file_exists(realpath(storage_path('app/public/'.$this->imgFolder))))
            //         \Storage::makeDirectory('app/public/'.$this->imgFolder, 0755, true, true);
           
           if (!\Storage::disk('public')->exists($this->imgFolder)) {
                \Storage::disk('public')->makeDirectory($this->imgFolder);
            } 

           $old_Image = (isset($this->image) && !empty($this->image)) ? substr(strrchr($this->image, '/'), 1) : '' ; 
            if(!empty($old_Image) && File::exists(public_path('/storage/' .$this->imgFolder. '/'.$old_Image)) )	
                    File::delete(public_path('storage/'.$this->imgFolder.'/'.$old_Image));	

           $filename = $this->generateImageName($value);
           $this->resizeImage($this->imgFolder, $value, $filename);
           
           $this->attributes['image'] = $filename; // استخدمي الاسم اللي حسبتيه مباشرة
   }
}

    static function getImg($imageFolder,$filename){
        $base_url = url('/');
        return (!empty($filename)) ? $base_url . '/storage/' .$imageFolder. '/'. $filename : asset('/assets/img/'.$this->avatar);
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

        $width  = $this->imageWidth ?? 1000;
        $height = $this->imageHeight ?? 1000;

        $directory = storage_path('app/public/'.$path);

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $image = $manager->read($photo);

        if ($image->width() >= $width && $image->height() >= $height) {
            $image->cover($width, $height);
        }

        $image->save($directory.'/'.$filename, quality: 80);

        return $filename;
    }



}

