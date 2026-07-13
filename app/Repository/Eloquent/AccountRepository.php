<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\AccountInterface;
use App\Models\Account;
use App\Models\Customer;
use App\Models\User;
use App\Models\PharmacyGroup;
use App\Models\Classes;
use App\Models\AccType;
use App\Http\Resources\API\AccountResource;
use App\Http\Resources\API\PharmacyGroupResource;

class AccountRepository implements AccountInterface
{
      
	  public function getAll($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) ? (request()->get('per_page') > 0 ? request()->get('per_page') : 100000) : 20;
			$accounts = $this->getAccountQuery();
		
		
	    $accounts = (clone $accounts)->filter($request)->orderBy('accounts.created_at','DESC')->paginate($limit);
		   $data = AccountResource::collection($accounts);
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }



	  public function getUserAccount($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) ? (request()->get('per_page') > 0 ? request()->get('per_page') : 100000) : 20;

		$accounts = $this->getUserAccountQuery(auth()->user());
		
		
	    $accounts = (clone $accounts)->filter($request)->orderBy('accounts.created_at','DESC')->paginate($limit);
		   $data = AccountResource::collection($accounts);
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }

	  public function createAccount($request){
		
		try {
			\DB::beginTransaction();
				 $account = Account::updateOrCreate(['name'=>$request->name],$request->validated());
				\DB::commit();
				return ['status'=>true,'message'=>trans('messages.success'),'data'=>new AccountResource($account)];
			} catch (\Exception $e) {
				\DB::rollback();
				return ['status'=>false,'message'=>trans('messages.server_error')];
			}
	  }

	  public function updateAccount($request,$account){
		try {
			\DB::beginTransaction();
			   if(!$account)
			   return ["status"=>false, "message"=>trans('messages.data_not_found')];
	
				$account->update($request->validated());
				\DB::commit();
				return ["status"=>true, "message"=>trans('messages.success'),'data'=>new AccountResource($account)];
			} catch (\Exception $e) {
				\DB::rollback();
				return ["status"=>false, "message"=>trans('messages.server_error')];
			}
	  }

	public function show($account){

		if(!$account)
		return ["status"=>false, "message"=>trans('messages.data_not_found')];

		return ["status"=>true, "message"=>trans('messages.success'),'data'=>new AccountResource($account)];	
   }

	public function deleteAccount($account)
    {
	   try {	
		if(!$account)
		    return ["status"=>false, "message"=>trans('messages.data_not_found')];

        $account->delete();
        return ["status"=>true, "message"=>trans('messages.success')];
		 }catch (\Exception $e) {
			return ["status"=>false, "message"=>trans('messages.server_error')];
		}

    }


	protected function getAccountQuery(){
       return  Account::select('accounts.*')->join('acc_type','acc_type.id','=','accounts.acc_type_id') ;
	   
	}


	protected function getUserAccountQuery($user){
       return $user->accounts()->join('acc_type','acc_type.id','=','accounts.acc_type_id')->groupBy('accounts.id');
	   
	}


  public function getAllPharmacyGroups($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) ? (request()->get('per_page') > 0 ? request()->get('per_page') : 100000) : 20;
	    $pharmacyGroups = PharmacyGroup::orderBy('created_at','DESC')->paginate($limit);
		   $data = PharmacyGroupResource::collection($pharmacyGroups);
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }


      
	  public function createPharmacyGroup($request){
		try {
			 \DB::beginTransaction();
				PharmacyGroup::updateOrCreate(['name'=>$request->name],$request->validated());
				\DB::commit();
				return ['status'=>true,'message'=>trans('messages.success')];
			} catch (\Exception $e) {
				\DB::rollback();
				return ['status'=>false,'message'=>trans('messages.server_error')];
			}

	  }

	  public function updatePharmacyGroup($request,$id){
		try {
			\DB::beginTransaction();
			  $pharmacygroups = PharmacyGroup::find($id);
			  if(!$pharmacygroups)
			      return ["status"=>false, "message"=>trans('messages.data_not_found')];
   
			   $pharmacygroups->update($request->validated());
			   \DB::commit();
			   return ["status"=>true, "message"=>trans('messages.success'),'data'=>$pharmacygroups];
		   } catch (\Exception $e) {
			   \DB::rollback();
			   return ["status"=>false, "message"=>trans('messages.server_error')];
		   }
	  }


    public function deletePharmacyGroup($id)
    {
		try {	
			$pharmacygroups = PharmacyGroup::find($id);
			if(!$pharmacygroups)
			return ["status"=>false, "message"=>trans('messages.data_not_found')];
	
			$pharmacygroups->delete();
			return ["status"=>true, "message"=>trans('messages.success')];
		 }catch (\Exception $e) {
			return ["status"=>false, "message"=>trans('messages.server_error')];
		}
    }


    public function showPharmacyGroup($pharmacygroups){

		if(!$pharmacygroups)
		return ["status"=>false, "message"=>trans('messages.data_not_found')];

		return ["status"=>true, "message"=>trans('messages.success'),'data'=>new PharmacyGroupResource($pharmacygroups)];	
   }


   
    public function getAccountCharts(){
        
        $is_pharmacy = request()->get('is_pharmacy') ? request()->get('is_pharmacy') : 0;
        $chartData = $this->drawAccountChart($is_pharmacy);
        $staticticsData = $this->drawAccountStatictics($is_pharmacy);

         $data = ['chart' => $chartData ,'staticticsData'=> $staticticsData];
        return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
   } 


   protected function drawAccountChart($is_pharmacy){
    $accounts = Account::select(\DB::raw('acc_type.name as acc_name, COUNT(accounts.id) as count'))
                    ->join('acc_type', 'acc_type.id', '=', 'accounts.acc_type_id')
                    ->where('acc_type.is_pharmacy',$is_pharmacy)
                    ->groupBy('accounts.acc_type_id')
                    ->orderBy('accounts.acc_type_id', 'asc')
                    ->get();

    // Transform the data for the chart
        $chartData = $accounts->map(function($account) {
            return ['name' => $account->acc_name,  'count' => $account->count];
        });

    return $chartData;
   }

     protected function drawAccountStatictics($is_pharmacy){
            $classses = Classes::get()->toArray();
            $account_type = AccType::where('acc_type.is_pharmacy',$is_pharmacy)->get();
            $accounts = Account::select(\DB::raw('acc_type.name as acc_name, classes.name as class_name, acc_type_id , class_id,   COUNT(accounts.id) as count'))
            ->join('acc_type', 'acc_type.id', '=', 'accounts.acc_type_id')
            ->join('classes', 'classes.id', '=', 'accounts.class_id')
            ->where('acc_type.is_pharmacy',$is_pharmacy)
            ->groupBy('accounts.acc_type_id')
            ->groupBy('accounts.class_id')
            ->orderBy('accounts.acc_type_id', 'asc')
            ->get();


        $accountData = $accounts->mapWithKeys(function($account) {  return [
                      $account->acc_type_id . '-' . $account->class_id => $account->count
            ];});
        $staticticsData = [];
        $staticticsData = $account_type->map(function($account_type) use ($classses,$accountData) {
                
                return [
                     'name' => $account_type['name'],
                      'classes_data'=> array_map(function($class) use ($accountData,$account_type) {
                        $key = $account_type['id'] . '-' . $class['id'];
                        return [
                            'id' => $class['id'],
                            'name' => $class['name'], 
                            'count' => $accountData->get($key, 0) 
                        ];
                      }, $classses)
                    ];
            });
    
           

         return $staticticsData;
   }

}
