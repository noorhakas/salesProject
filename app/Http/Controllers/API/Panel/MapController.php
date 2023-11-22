<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Enums\VisitStatusEnum;
use App\Repository\Interfaces\VisitInterface;
use Carbon\Carbon;

class MapController extends Controller
{

	public function getMaps(Request $request, VisitInterface $IVisit)
	{
		$lat = $request->lat ?? 29.378586;
		$lng = $request->lng ?? 47.990341;
		$startDate = Carbon::now()->startOfMonth()->toDateString();
		$endDate =Carbon::now()->endOfMonth()->toDateString();
        $limit = 100;
		$accounts = Account::select(['id','name','lat','lng'])->where('lat','>=',$lat)->where('lng','<=',$lng);
		$accountIds = (clone $accounts)->orderBy('accounts.lat','desc')->paginate($limit)->pluck('id');
		$visits = (clone $IVisit->DrawVisitCountStatistics())->whereDate('visits.visit_date','>=',$startDate)->whereDate('visits.visit_date','<=',$endDate)
				           ->where(['visits.status'=>VisitStatusEnum::Visited])->whereIn('visits.account_id',$accountIds)
						  ->orderBy('visit_count','desc')->groupBy('visits.account_id')->get()->keyBy('account_id');
		$locations = (clone $accounts)->selectraw('id as account_id,name,lat,lng')->orderBy('accounts.lat','desc')->paginate($limit)->map(fn($item)=>
				[
					"id"=>$item->account_id,
					"name"=>$item->name,
					"lat"=>$item->lat??'',
					"lng"=>$item->lng??'',
					"visits"=> isset($visits[$item->account_id])? $visits[$item->account_id]->visit_count : 0,
				]);

		return $this->SendResponse(["status"=>true, "message"=>trans('messages.success'),'data'=>$locations]);
	}

}