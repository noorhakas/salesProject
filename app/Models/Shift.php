<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    //
     use SoftDeletes;
    
    protected $table = 'shifts';

    protected $fillable = ['name','time_from','time_to'];


}
