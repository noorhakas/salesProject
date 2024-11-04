<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ObservantTrait;

class PharmacyGroup extends Model
{
	use SoftDeletes,ObservantTrait;
    protected $table = 'pharmacy_group';
	
	protected $fillable = ['name'];


}