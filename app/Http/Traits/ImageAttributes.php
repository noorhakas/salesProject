<?php

namespace App\Http\Traits;
use Illuminate\Support\Str;
use Image, File;
use Carbon\Carbon;

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
				if(!file_exists(realpath(storage_path('app/public/'.$this->imgFolder))))
						\Storage::makeDirectory('app/public/'.$this->imgFolder, 0755, true, true);

               $old_Image = (isset($this->image) && !empty($this->image)) ? substr(strrchr($this->image, '/'), 1) : '' ; 
				if(!empty($old_Image) && File::exists(public_path('/storage/' .$this->imgFolder. '/'.$old_Image)) )	
				        File::delete(public_path('storage/'.$this->imgFolder.'/'.$old_Image));	

			   $values =$this->resizeImage($this->imgFolder,$value,$this->generateImageName($value));// $value->storeAs($this->imgFolder,$this->generateImageName($value),"public");
			 // dd($values);
               $arrVal =$values->basename;
			   $this->attributes['image']=$arrVal;
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


    public function resizeImage( $path ,$photo, $filename  )
    {
        $manager = new \Intervention\Image\ImageManager();
        $image = $manager->make($photo)->save(storage_path('app/public/'.$path.'/' .$filename) ,40);
        return $image;

    }



}

