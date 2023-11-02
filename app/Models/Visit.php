<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
	use SoftDeletes;
    protected $table = 'visits';
	protected $fillable = ['user_id','customer_id','type','status','visit_date','start_time','end_time','confirmed_by' ,'notes'];

	
	public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

	public function visitdetails()
    {
        return $this->hasMany(VisitDetails::class);
    }

	
}