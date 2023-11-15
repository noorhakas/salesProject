<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Carbon\Carbon;
use App\Enums\VisitStatusEnum;

class VisitStatisticsResource extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

	
    /**
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {

       return  [
            'id' => $this->id??'',
            'name' => $this->name??'',
			'visit_count'=>$this->visit_count??0,
			'pln_visit_count'=>$this->pln_visit_count??0,
			'unpln_visit_count'=>$this->unpln_visit_count??0,
			'missed_visit_count'=>$this->missed_visit_count??0,
			'false_visit_count'=>$this->false_visit_count??0,
			'pending_count'=>$this->pending_count??0,
        ];
    }


	public static function collection($resource)
    {
        return tap(new GlobalCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
   }
   
}
