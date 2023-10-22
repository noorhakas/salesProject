<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Specialty extends Model
{
	use SoftDeletes;
    protected $table = 'specialty';
	protected $fillable = ['name'];

	public function products() : HasMany
    {
        return $this->hasMany(Product::class);
    }
}