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
	
	protected $fillable = ['app_name' , 'image' , 'map_key','allow_distance','phone','shift_time_from','shift_time_to','weekly_off_days'];

	  protected $casts = [
        'weekly_off_days' => 'array',
    ];

}