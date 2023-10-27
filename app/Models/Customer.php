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
	
	protected $fillable = ['name','brick_id','class_id','specialty_id','acc_type_id','image','brief','address','lat','lng'];

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
        return $this->belongsTo(AccList::class,'acc_type_id','id');
    }
}