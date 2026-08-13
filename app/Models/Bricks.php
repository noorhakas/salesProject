<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ObservantTrait;

class Bricks extends Model
{
    use SoftDeletes, ObservantTrait;

    protected $table = 'bricks';

    protected $fillable = ['name', 'branch_id'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_bricks', 'brick_id', 'user_id');
    }

    public function scopeFilter($q, $request)
    {
        return $q
            ->when(
                $request->filled('user_id'),
                fn ($q) => $q->whereHas('users', fn ($u) => $u->where('users.id', $request->user_id))
            )
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where('bricks.name', 'like', '%' . $request->search . '%')
            )
            ->when(
                $request->filled('branch_id'),
                fn ($q) => $q->where('bricks.branch_id', $request->branch_id)
            );
    }
}