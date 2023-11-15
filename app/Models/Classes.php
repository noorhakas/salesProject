<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ObservantTrait;

class Classes extends Model
{
	use SoftDeletes,ObservantTrait;
    protected $table = 'classes';

	protected $fillable = ['name','frequency'];

}