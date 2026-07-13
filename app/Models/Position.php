<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ObservantTrait;

class Position extends Model
{
	use SoftDeletes, ObservantTrait;
    protected $table = 'positions';
	
	protected $fillable = ['ps_key','name','parent_id'];

   public function children()
{
    return $this->hasMany(Position::class, 'parent_id');
}
}