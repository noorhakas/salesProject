<?php

namespace App\Repository;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Models\Plan;
use App\Models\Visit;
use App\Models\Gift;
use App\Http\Resources\API\VisitDetailResource;
use App\Http\Resources\API\VisitsResource;

use App\Enums\GiftTypeEnum;



class VisitRepository{
     public function getVisitDetail(Visit $visit){
          
		$visitProductItem = $this->getVisitItemList($visit,0); //type -- products
		$listOfProduct= $this->mergeDataById($this->getUserProducts(),$visitProductItem);

       $visitleaveBehind = $this->getVisitItemList($visit,GiftTypeEnum::LeaveBehind);
	   $listOfLeaveBehind= $this->mergeDataById($this->getGifts(GiftTypeEnum::LeaveBehind),$visitleaveBehind);

	   $visitGifts = $this->getVisitItemList($visit,GiftTypeEnum::Gift);
	   $listOfGist= $this->mergeDataById($this->getGifts(GiftTypeEnum::Gift),$visitGifts);

	   $visitAdditionalFiles = $this->getVisitItemList($visit,GiftTypeEnum::AdditionalFiles);
	   $listOfAdditionalFiles= $this->mergeDataById($this->getGifts(GiftTypeEnum::AdditionalFiles),$visitAdditionalFiles);

		return 
		[
			"visit"=>new VisitsResource($visit),
			"products"=>VisitDetailResource::collection($listOfProduct),
			"leaveBehind"=>VisitDetailResource::collection($listOfLeaveBehind),
			"Gifts"=>VisitDetailResource::collection($listOfGist),
			"AdditionalFiles"=>VisitDetailResource::collection($listOfAdditionalFiles),
		];
	 }


	 protected function getUserProducts(){
		return auth()->user()->products()->selectRaw('products.id , products.name ,0 as count_of_sample , 0 as checked')->get(['products.id','products.name']);
	 }

	 protected function getGifts($type = GiftTypeEnum::Gift){
		return Gift::selectRaw('id , name ,0 as count_of_sample , 0 as checked')->where('type',$type)->get();
	 }

	 protected function getVisitItemList(Visit $visit ,$type = 0){
		switch($type){
			case 0:
				return $visit->visitdetailProducts()->selectRaw('item_id as id ,count_of_sample, 1 as checked ')->get()->keyBy('id');
			break;
			default:
			   return $visit->visitdetailGifts()->selectRaw('item_id as id ,count_of_sample, 1 as checked ')->where('item_type',$type)->get()->keyBy('id');
			break;
		}
         
	 }
	 public function mergeDataById(Collection ...$collections)
    {
        $data = [];

        foreach ($collections as $collection) {
            foreach ($collection as $Id => $item) {
                if (!$item instanceof Collection) {
                    $item = collect($item);
                };
				$data[$Id] = ReportData::make(array_merge(isset($data[$Id]) ? $data[$Id]->toArray() : ['id' => $Id], $item->toArray()));
            }
        }
        return collect($data)->sortBy('id', SORT_REGULAR, false)->values();
    }
}

class ReportData extends Collection
{
    public function __get($name)
    {
        return $this->get($name, null);
    }
}


