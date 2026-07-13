<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBricks extends Model
{
    public $timestamps = false;
    protected $table = 'user_bricks';
	
	protected $fillable = ['user_id','brick_id'];


}
