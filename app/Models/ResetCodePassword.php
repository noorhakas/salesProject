<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResetCodePassword extends Model
{
    protected $table = 'password_resets';

	const UPDATED_AT = null;
	protected $fillable = ['email' ,'token'];


}