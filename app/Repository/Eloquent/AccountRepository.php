<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\AccountInterface;
use App\Models\Account;
use App\Models\Customer;
use App\Models\User;

use App\Http\Resources\API\AccountResource;

class AccountRepository implements AccountInterface
{
      
	  public function getAll($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) ? (request()->get('per_page') > 0 ? request()->get('per_page') : 100000) : 20;
		if(request()->get('user_id') && !empty(request()->get('user_id'))){
			$user = User::find(request()->get('user_id'));
			$accounts = $this->getAccountQuery($user);
		}else{
			$accounts = $this->getAccountQuery(auth()->user());
		}
		
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
				return ["status"=>true, "message"=>trans('messages.success'),'data'=>new AccountResource($customer)];
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


	protected function getAccountQuery($user){
       return ($user->access_all_data)
                           ? Account::select('accounts.*')->join('acc_type','acc_type.id','=','accounts.acc_type_id') :
						    $user->accounts()->join('acc_type','acc_type.id','=','accounts.acc_type_id')->groupBy('accounts.id');
	   
	}

}