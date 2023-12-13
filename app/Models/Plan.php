<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Http\Traits\ObservantTrait;

class Plan extends Model
{
	use SoftDeletes,ObservantTrait;
    protected $table = 'plans';
	protected $fillable = ['Uuid','user_id','type','start_date','end_date','status','approved_or_rejected_by'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

	public static function boot()
    {
        parent::boot();
        static::creating(function ($model){
            $model->Uuid = self::generateNumber();
        });
    }
    public static function generateNumber()
    {
        $number =str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        if(self::where('Uuid', $number)->count()){
            $number = self::generateNumber();
        }
        return $number;
    }

	public function user()
    {
        return $this->belongsTo(User::class);
    }

	public function manager()
    {
        return $this->belongsTo(User::class,'approved_or_rejected_by','id');
    }

	public function visits()
    {
        return $this->hasMany(Visit::class);
    }


	public function plan_status()
    {
        return $this->hasMany(PlanStatus::class);
    }

	public function scopeFilter($q,$request)
    {
		$q = $q->when($request->search,fn($q, $v) => 
				$q->where('Uuid', 'like', "%{$v}%"))
			->when($request->date,fn($q, $v) => 
				$q->whereDate('plans.end_date', '<', $v))
			->when($request->user_id,fn($q, $v) => 
				$q->where('plans.user_id', $v))
				->when($request->status,function($q) use ($request){
                    if($request->status == 3){
						$q->where(function ($q) use ($request){
							 $q->where('plans.status',$request->status)
							 ->orWhere(function($q2){
								$q2->where('plans.status','!=' ,2)->whereDate('plans.end_date','<',Carbon::now()->toDateString());
								});
							});
					}elseif($request->status == 4){
						$q->where('plans.status', 1)->whereDate('plans.end_date','>=',Carbon::now()->toDateString())
							->whereDate('plans.start_date','<=',Carbon::now()->toDateString());
					}
					elseif($request->status == 1){
						$q->where('plans.status', 1)
							->whereDate('plans.start_date','>=',Carbon::now()->toDateString());
					}
					else{
					 $q->where('plans.status',$request->status);
					}
				});
        return $q;
    }
}