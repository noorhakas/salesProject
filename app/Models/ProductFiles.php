<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ObservantTrait;
use App\Http\Traits\FileAttributes;

class ProductFiles extends Model
{
	use SoftDeletes,FileAttributes,ObservantTrait;
    protected $table = 'product_files';
	protected $imgFolder = 'product_files';
	protected $vatar = 'avatar_logo.jpg';
	
	protected $fillable = ['product_id' ,'file'];


	public function product()
    {
        return $this->belongsTo(Product::class);
    }



}
