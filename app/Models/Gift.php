<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ObservantTrait;

class Gift extends Model
{
	use SoftDeletes,ObservantTrait;
    protected $table = 'gifts';

	protected $fillable = ['name' ,'type'];


}