<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Models\ObservantLogTrait;

class OvertimeRequest extends Model
{
    //
    use ObservantLogTrait;
    protected $table = 'overtime_requests';
    protected $fillable = ['user_id','year','month',
    'total_minutes','approved_minutes','status','approved_by','approved_at'];

  
    public function user()
{
    return $this->belongsTo(User::class);
}

public function approver()
{
    return $this->belongsTo(User::class, 'approved_by');
}

}
