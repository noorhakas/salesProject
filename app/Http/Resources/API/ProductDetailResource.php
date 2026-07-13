<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\GlobalCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use App\Enums\StatusEnum;
use App\Models\Product;
use Carbon\Carbon;

class ProductDetailResource extends JsonResource
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
         
      $base =  [
            'id' => $this->id,
             'Uuid' => $this->Uuid,
            'name' => $this->name,
			'image'=>$this->image,
			'category_id'=>$this->category_id,
			'specialty_name'=>(string)optional($this->category)->name,
			'company_id'=>$this->company_id,
			'company_name'=>(string)optional($this->company)->name,
			'price'=>(float)$this->price,
			'description'=>$this->description,
			'files'=>$this->productfiles()->get(['id','file']),
                        'status'=>$this->status,
                        'statusAsString'=>StatusEnum::toString($this->status),
                        'created_at'=>Carbon::parse($this->created_at)->toDayDateTimeString(),
          'similar_items' => Product::where('category_id', $this->category_id)
                ->where('id', '!=', $this->id)
                ->get(['id', 'name', 'price', 'image']),

        ];


		return $base;

    }

	
}
