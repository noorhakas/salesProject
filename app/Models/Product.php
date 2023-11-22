<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ImageAttributes;
use App\Http\Traits\ObservantTrait;

class Product extends Model
{
	use SoftDeletes, ImageAttributes, ObservantTrait;
    protected $table = 'products';
	protected $imgFolder = 'products';
	protected $avatar = 'medicine_logo.png';


	protected $fillable = ['name','specialty_id','image','description','price'];


	public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

	public function productfiles()
    {
        return $this->hasMany(ProductFiles::class);
    }

	public function productNotes()
    {
        return $this->belongsToMany(User::class, 'product_notes','product_id','user_id');
    }

}