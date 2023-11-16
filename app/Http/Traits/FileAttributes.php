<?php

namespace App\Http\Traits;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Image, File;
use Carbon\Carbon;

trait FileAttributes
{
    /**
     * @return null|string
     */
	 public function getFileAttribute(){
		if (isset($this->attributes['file']) && !filter_var($this->attributes['file'], FILTER_VALIDATE_URL) === false) 
		{
			return $this->attributes['file'];
		}else{
             return isset($this->imgFolder) && !empty($this->attributes['file']) && file_exists(public_path('storage/'.$this->imgFolder.'/'.$this->attributes['file'])) 
                   ? self::getFile($this->imgFolder,$this->attributes['file']) : '';
		}
    }

    /**
     * @param $value
     */
   public function setFileAttribute($value){
		$base_url = url('/');
		
	   if (!empty($value)){
				if(!file_exists(realpath(storage_path('app/public/'.$this->imgFolder))))
						\Storage::makeDirectory('app/public/'.$this->imgFolder, 0755, true, true);

               $old_File = (isset($this->file) && !empty($this->file)) ? substr(strrchr($this->file, '/'), 1) : '' ; 
				if(!empty($old_File) && File::exists(public_path('/storage/' .$this->imgFolder. '/'.$old_File)) )	
				        File::delete(public_path('storage/'.$this->imgFolder.'/'.$old_File));	

			   $values = $value->storeAs($this->imgFolder,$this->generateImageName($value),"public");
			   $arrVal =explode('/',$values);
			   $this->attributes['file']=Str::snake($arrVal[count($arrVal)-1]);
	   }
   }

    static function getFile($imageFolder,$filename){
        $base_url = url('/');
        return (!empty($filename)) ? $base_url . '/storage/' .$imageFolder. '/'. $filename : '';
    }


	function generateImageName($file){
        $fileNameWithExt = $file->getClientOriginalName();
        $filename = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
        $extention = ($file->getClientOriginalExtension()) ? strtolower($file->getClientOriginalExtension()) :'ogg';
        $fileNameToStore = $filename.'_'.time().'.'.$extention;
        return Str::snake($fileNameToStore);
    }


}

