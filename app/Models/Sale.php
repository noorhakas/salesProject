<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ObservantTrait;

class Sale extends Model
{
	use SoftDeletes;
    protected $table = 'sales';

	protected $fillable = ['product_id','account_id','unit','price','total_price','month_date','user_id'];


    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function account() {
        return $this->belongsTo(Account::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}