<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ObservantTrait;
use App\Http\Traits\FileAttributes;

class Gift extends Model
{
	use SoftDeletes,ObservantTrait,FileAttributes;
    protected $table = 'gifts';
	protected $imgFolder = 'gifts';
	protected $vatar = 'avatar_logo.jpg';
	
	protected $fillable = ['name' ,'type','file'];


}