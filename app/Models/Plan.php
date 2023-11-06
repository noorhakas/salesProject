<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Plan extends Model
{
	use SoftDeletes;
    protected $table = 'plans';
	protected $fillable = ['Uuid','user_id','type','start_date','end_date'];

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

	public function visits()
    {
        return $this->hasMany(Visit::class);
    }


	public function scopeFilter($q,$request)
    {
		$q = $q->when($request->search,fn($q, $v) => 
					$q->where('Uuid', 'like', "%{$v}%"));

        return $q;
    }
}