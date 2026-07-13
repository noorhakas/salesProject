<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\FileAttributes;
use App\Http\Traits\ObservantTrait;


class Account extends Model
{
    use SoftDeletes, FileAttributes ,ObservantTrait;
    protected $table = 'accounts';
	protected $fillable = ['name','brick_id','phone','phone1','acc_type_id','address','lat','lng','class_id','pharmacy_group_id'];

	public function brick()
    {
        return $this->belongsTo(Bricks::class);
    }
	public function accType()
    {
        return $this->belongsTo(AccType::class,'acc_type_id','id');
    }

	public function customers()
    {
        return $this->hasMany(Customer::class);
    }

	public function class()
    {
        return $this->belongsTo(Classes::class);
    }
	

      public function pharmacyGroup()
    {
        return $this->belongsTo(PharmacyGroup::class,'pharmacy_group_id','id');
    }

	public function scopeFilter($q,$request)
    {
		$q = $q->when($request->search,fn($q, $v) => 
					$q->where('accounts.name', 'like', "%{$v}%"))
					->when(isset($request->is_pharmacy),function($q) use ($request){
						 $q->where('acc_type.is_pharmacy', $request->is_pharmacy);	
					})->when($request->acc_type_id,fn($q, $v) => 
					$q->where('acc_type_id', $v))
                    ->when($request->class_id,fn($q, $v) => 
					$q->where('accounts.class_id', $v))
                    ->when($request->pharmacy_group_id,fn($q, $v) => 
					$q->where('accounts.pharmacy_group_id', $v));		

        return $q;
    }
}