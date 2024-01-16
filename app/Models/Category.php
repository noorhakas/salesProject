<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ObservantTrait;

class Category extends Model
{
	use SoftDeletes,ObservantTrait;
    protected $table = 'category';
	
	protected $fillable = ['name'];


}