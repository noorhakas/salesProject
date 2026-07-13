<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ImageAttributes;
use App\Http\Traits\ObservantTrait;

class Product extends Model
{
	use SoftDeletes, ImageAttributes, ObservantTrait;
    protected $table = 'products';
	protected $imgFolder = 'products';
	protected $avatar = 'medicine_logo.png';


	protected $fillable = ['Uuid','name','specialty_id','image','description','price','company_id','category_id','status'];



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

	public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

	public function category()
    {
        return $this->belongsTo(Category::class);
    }

	public function company()
    {
        return $this->belongsTo(Company::class);
    }

	public function productfiles()
    {
        return $this->hasMany(ProductFiles::class);
    }

	public function productNotes()
    {
        return $this->belongsToMany(User::class, 'product_notes','product_id','user_id');
    }

    public function departments()
	{
		return $this->belongsToMany(
			Department::class,
			'department_product'
		);
	}

}