<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccounts extends Model
{
    protected $table = 'user_customers';
	
	protected $fillable = ['user_id','customer_id','account_id'];


}