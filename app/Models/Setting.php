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
	
	protected $fillable = ['app_name' , 'image' , 'map_key','allow_distance','phone','email',
	                           'shift_time_from','shift_time_to','weekly_off_days',
							   'android_build' ,'ios_build' 
							   ];

	  protected $casts = [
        'android_build' => 'array',
        'ios_build' => 'array',
        'weekly_off_days' => 'array',
      ];

} 