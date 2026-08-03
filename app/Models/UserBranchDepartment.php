<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBranchDepartment extends Model
{
    protected $fillable = ['user_id', 'branch_id', 'department_id'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}