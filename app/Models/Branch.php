<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
	use SoftDeletes;
    protected $table = 'branches';

	protected $fillable = ['name','address','phone'];

	public function users()
	{
		return $this->belongsToMany(User::class, 'user_branches', 'branch_id', 'user_id');
	}


	public function departments()
	{
		return $this->belongsToMany(
			Department::class,
			'branch_departments'
		);
	}

}