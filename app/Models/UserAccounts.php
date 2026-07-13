<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccounts extends Model
{
    public $timestamps = false;
    protected $table = 'user_customers';
	
	protected $fillable = ['user_id','customer_id','account_id'];


}
