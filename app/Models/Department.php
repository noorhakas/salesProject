<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
	use SoftDeletes;
    protected $table = 'departments';

	protected $fillable = ['name'];

	public function users()
	{
		return $this->belongsToMany(User::class, 'user_departments', 'department_id', 'user_id');
	}

	public function branches()
	{
		return $this->belongsToMany(
			Branch::class,
			'branch_departments'
		);
	}

	public function products()
	{
		return $this->belongsToMany(
			Product::class,
			'department_product'
		);
	}

}