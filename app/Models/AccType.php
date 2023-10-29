<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccType extends Model
{
	use SoftDeletes;
    protected $table = 'acc_type';
	
	protected $fillable = ['name'];


}