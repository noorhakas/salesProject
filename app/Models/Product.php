<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\FileAttributes;

class Product extends Model
{
	use SoftDeletes, FileAttributes;
    protected $table = 'products';
	protected $imgFolder = 'products';
	protected $avatar = 'medicine_logo.png';


	protected $fillable = ['name','specialty_id','image','description','price'];


	public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }
}