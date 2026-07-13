<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicHoliday extends Model
{
     use SoftDeletes;
    //
    protected $table = 'public_holiday';
    protected $fillable = [
        'name',
        'date_from',
        'date_to',
        'type',
        'active',
    ];

   
}
