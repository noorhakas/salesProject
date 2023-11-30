<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ObservantTrait;
use Carbon\Carbon;

class Visit extends Model
{
	use SoftDeletes, ObservantTrait;
    protected $table = 'visits';
	protected $fillable = ['plan_id','user_id','account_id','customer_id','type','status','visit_date','start_time','end_time','confirmed_by' ,'notes','user_location_lat','user_location_lng','actual_start_date','actual_end_date'];

	public function account()
    {
        return $this->belongsTo(Account::class);
    }

	public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

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
					$q->where('visits.customer_id', $v))
                ->when($request->status,function($q) use ($request){
                    if($request->status == 5)
		               $q->where('visits.status',0)->where('visit_date','<',Carbon::now()->toDateString());	
					else
					 $q->where('visits.status',$request->status);
				});
				    
        return $q;
    }

	
}