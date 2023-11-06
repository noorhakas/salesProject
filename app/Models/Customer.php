<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\FileAttributes;

class Customer extends Model
{
	use SoftDeletes, FileAttributes;
    protected $table = 'customers';
	protected $imgFolder = 'customers';
	protected $vatar = 'avatar_logo.jpg';
	
	protected $fillable = ['name','brick_id','class_id','specialty_id','image','phone','phone1','acc_type_id','image','brief','address','lat','lng'];

	public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }
	public function class()
    {
        return $this->belongsTo(Classes::class);
    }
	public function brick()
    {
        return $this->belongsTo(Bricks::class);
    }
	public function accType()
    {
        return $this->belongsTo(AccType::class,'acc_type_id','id');
    }

	public function scopeFilter($q,$request)
    {
		$q = $q->when($request->search,fn($q, $v) => 
					$q->where('name', 'like', "%{$v}%"))
					->when($request->acc_type_id,fn($q, $v) => 
					$q->where('acc_type_id', $v)) ;		

        return $q;
    }

}