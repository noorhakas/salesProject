<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
	use SoftDeletes;
    protected $table = 'visits';
	protected $fillable = ['plan_id','user_id','customer_id','type','status','visit_date','start_time','end_time','confirmed_by' ,'notes','user_location','acc_type_id'];

	
	public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

	public function user()
    {
        return $this->belongsTo(User::class);
    }

	public function visitdetails()
    {
        return $this->hasMany(VisitDetails::class);
    }
	

	public function visitdetailProducts()
    {
        return $this->hasMany(VisitDetails::class)->where('item_type',0);
    }

	public function visitdetailGifts()
    {
        return $this->hasMany(VisitDetails::class)->where('item_type',1);
    }

	public function scopeFilter($q,$request)
    {
		$q = $q->when($request->plan_id,fn($q, $v) =>
		       $q->where('visits.plan_id', $v))
		       ->when($request->search,fn($q, $v) =>
		        $q->where('customers.name', 'like', "%{$v}%"))
				->when($request->visit_date,fn($q, $v) =>
					$q->where('visits.visit_date', $v))
				->when($request->user_id,fn($q, $v) =>
					$q->where('visits.user_id', $v))
				->when($request->customer_id,fn($q, $v) =>
					$q->where('visits.customer_id', $v));
        return $q;
    }

	
}