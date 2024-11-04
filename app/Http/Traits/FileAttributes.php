<?php

namespace App\Http\Traits;
use Illuminate\Support\Str;
use Image, File;
use Carbon\Carbon;
use Illuminate\Support\LazyCollection;

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
                   ?  self::getFile($this->imgFolder,$this->attributes['file']) : '';
		}
    }

    /**
     * @param $value
     */
   public function setFileAttribute($value){
		$base_url = url('/');
	   if (!empty($value)){
        $path = $this->imgFolder;
				if(!file_exists(realpath(storage_path('app/public/'.$path))))
						\Storage::makeDirectory('app/public/'.$path, 0755, true, true);

               $old_File = (isset($this->file) && !empty($this->file)) ? substr(strrchr($this->file, '/'), 1) : '' ; 
				if(!empty($old_File) && File::exists(public_path('/storage/' .$path.'/'.$old_File)) )	
				        File::delete(public_path('storage/'.$path.'/'.$old_File));	

                // $values =$this->resizeFile($path,$value,$this->generateImageName($value));
               $values = $value->storeAs($path,$this->generateImageName($value),"public");
                        $arrVal =explode('/',$values);
                                        // Get the basename of the new file
                        $this->attributes['file'] = Str::snake($arrVal[count($arrVal)-1]); //  Save the filename  
			 			  
	   }
   }

    static function getFile($imageFolder,$filename){
        $base_url = url('/');
        return (!empty($filename)) ? $base_url . '/storage/' .$imageFolder. '/'. $filename : '';
    }

	function generateImageName($file){
        $fileNameWithExt = $file->getClientOriginalName();
        $filename = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
        $extention =strtolower($file->getClientOriginalExtension());
        $fileNameToStore = $filename.'_'.time().'.'.$extention;
        return Str::snake($fileNameToStore);
    }

    public function resizeFile( $path ,$photo, $filename  )
    {
        $manager = new \Intervention\Image\ImageManager();
        $image = $manager->make($photo)->save(storage_path('app/public/'.$path.'/' .$filename) ,40);
        return $image;
    }


  


}

