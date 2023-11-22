<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ObservantTrait;

class ProductNotes extends Model
{
	use SoftDeletes,ObservantTrait;
	
	protected $fillable = ['product_id' ,'user_id','note'];


	public function product()
    {
        return $this->belongsTo(Product::class);
    }



}