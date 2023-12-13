<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanStatus extends Model
{
    protected $table = 'plan_status';	
	protected $fillable = ['plan_id' ,'status','approved_or_rejected_by','note'];


	public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

	public function manager()
    {
        return $this->belongsTo(User::class,'approved_or_rejected_by','id');
    }



}