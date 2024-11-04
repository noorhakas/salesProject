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
	protected $fillable = ['plan_id','user_id','account_id','customer_id','type','status','visit_date','start_time','end_time','confirmed_by' ,'notes','user_location_lat','user_location_lng','actual_start_date','actual_end_date','combine_with'];

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


    public function doubleVisit()
    {
        return $this->belongsTo(User::class,'combine_with','id');
    }
	
    public function getStatusAttribute($value)
    {
        return (Carbon::parse($this->visit_date)->toDateString() < Carbon::now()->toDateString()) && $value != 2  ? 5 : $value;
    }

    public function scopeFilter($q,$request)
    {
          $status =  isset($request->status) && $request->status == 0 ? "-1" : $request->status;

		$q =$q->when($request->plan_id,fn($q, $v) =>
                   $q->where('visits.plan_id', $v))
            ->when($status,function($q) use ($status){
                            if($status == 5)
                             $q->where('visits.status',0)->where('visit_date','<',Carbon::now()->toDateString());
                            else if($status == "-1")
                             $q->where('visits.status',0)->where('visit_date','>=',Carbon::now()->toDateString());
                             else if($status == "-2")
                             $q->where('visits.type',0)	;
                             else if($status == "-3")
                             $q->where('visits.type',1);		
                            else
                            $q->where('visits.status',$status);
                })->when($request->search,fn($q, $v) => 
                    $q->where(function ($query) use ($v) {
                    $query->orWhere('customers.name', 'like', "%{$v}%")->orWhere('accounts.name', 'like', "%{$v}%"); }))
                    ->when($request->start_date,fn($q, $v) => 
                $q->where(function ($query) use ($v) {
                        $query->orWhere('visits.visit_date', '>=', $v)->orWhere('visits.actual_start_date', '>=', $v);  }))
                ->when($request->end_date,fn($q, $v) => 
                    $q->where(function ($query) use ($v) {
                        $query->orWhere('visits.visit_date', '<=', $v)->orWhere('visits.actual_start_date', '<=', $v);  }))   
                ->when($request->visit_date,fn($q, $v) =>
                        $q->where('visits.visit_date', $v))
                    ->when($request->user_id,fn($q, $v) =>
                        $q->where('visits.user_id', $v))
                    ->when($request->customer_id,fn($q, $v) =>
                        $q->where('visits.customer_id', $v));

        return $q;
    }

	
}