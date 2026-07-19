<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ObservantTrait;

class Specialty extends Model
{
	use SoftDeletes,ObservantTrait;
    protected $table = 'specialty';
	protected $fillable = ['name'];

	
}