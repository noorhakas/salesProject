<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProducts extends Model
{
    public $timestamps = false;
    protected $table = 'user_products';
	
	protected $fillable = ['user_id','product_id'];


}
