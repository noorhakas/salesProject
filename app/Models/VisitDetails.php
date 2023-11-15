<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitDetails extends Model
{

    protected $table = 'visit_details';
	protected $fillable = ['visit_id','item_id','count_of_sample','item_type'];

	public function item()
    {
		if($this->item_type == 0)
          return $this->belongsTo(Product::class ,'item_id','id');
		else
		 return $this->belongsTo(Gift::class ,'item_id','id');
    }
	
}