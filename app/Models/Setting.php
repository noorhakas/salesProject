<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\ObservantTrait;
use App\Http\Traits\ImageAttributes;


class Setting extends Model
{
	use ObservantTrait, ImageAttributes;
    protected $table = 'settings';
	protected $imgFolder = 'settings';
	protected $avatar = 'royal-logo.png';
	
	protected $fillable = ['app_name' , 'image' , 'map_key'];


}