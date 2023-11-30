<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ImageAttributes;
use App\Http\Traits\ObservantTrait;

class Customer extends Model
{
	use SoftDeletes, ImageAttributes, ObservantTrait;
    protected $table = 'customers';
	protected $imgFolder = 'customers';
	protected $avatar = 'avatar_logo.jpg';
	
	protected $fillable = ['name','account_id','specialty_id','image','phone','phone1','acc_type_id','image','brief','work_days','work_start_time','work_end_time'];

	protected $casts = [ 'work_days' => 'array' ];


	public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }
	
	public function account()
    {
        return $this->belongsTo(Account::class);
    }

	public function accType()
    {
        return $this->belongsTo(AccType::class,'acc_type_id','id');
    }

	public static function workDays(){
		return [
			(object) ['id'=>1,'name'=>'SAT'],
			(object) ['id'=>2,'name'=>'SUN'],
			(object) ['id'=>3,'name'=>'MON'],
			(object) ['id'=>4,'name'=>'TUES'],
			(object) ['id'=>5,'name'=>'WEND'],
			(object) ['id'=>6,'name'=>'THUR'],
			(object) ['id'=>6,'name'=>'FRI']
			
		];
    }
	public function scopeFilter($q,$request)
    {
		$q = $q->when($request->acc_type_id,fn($q, $v) => 
					$q->where('customers.acc_type_id', $v)) 
					->when($request->account_id,fn($q, $v) => 
					     $q->where('customers.account_id', $v))
					->when($request->search,fn($q, $v) => 
					$q->where('customers.name', 'like', "%{$v}%")->orWhere('accounts.name','like', "%{$v}%"));	 	

        return $q;
    }

}