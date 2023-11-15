<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ObservantTrait;

class AccType extends Model
{
	use SoftDeletes, ObservantTrait;
    protected $table = 'acc_type';
	
	protected $fillable = ['name'];


}