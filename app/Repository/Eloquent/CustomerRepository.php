<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\CustomerInterface;
use App\Models\Customer;
use App\Models\Account;
use App\Models\User;
use App\Http\Resources\API\CustomerResource;
use App\Http\Resources\API\AccountCustomerResource;

class CustomerRepository implements CustomerInterface
{
      
	  public function getAll($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) ? (request()->get('per_page') > 0 ? request()->get('per_page') : 100000) : 20;
		
		if(request()->get('user_id') && !empty(request()->get('user_id'))){
			$user = User::find(request()->get('user_id'));
			$customer = $this->getCustomerQuery($user);
		}else{
			$customer = $this->getCustomerQuery(auth()->user());
		}

		$customers = (clone $customer)->filter($request)->orderBy('created_at','DESC')->paginate($limit);
		   $data = CustomerResource::collection($customers);
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }


      public function FetchcustomersAccount($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) ? (request()->get('per_page') > 0 ? request()->get('per_page') : 100000) : 20;
		
		$accounts =Account::selectraw('accounts.id as id ,accounts.name as account_name ,customers.name as customer_name,customers.id as customer_id')->leftjoin('customers','customers.account_id','=','accounts.id');
		$customers = (clone $accounts)->filter($request)->orderBy('accounts.created_at','ASC')->paginate($limit);
		   $data = AccountCustomerResource::collection($customers);

		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }
	  

	  public function createCustomer($request){
		
		try {
		      \DB::beginTransaction();
                 $arr = array_merge($request->validated(),['work_days'=>isset($request->work_days) && !empty($request->work_days) ? array_unique(array_map('intval',$request->work_days))  : []]);
				 $customer = Customer::updateOrCreate(['name'=>$request->name],$arr);
				\DB::commit();
				return ['status'=>true,'message'=>trans('messages.success'),'data'=>new CustomerResource($customer)];
			} catch (\Exception $e) {
				\DB::rollback();
				return ['status'=>false,'message'=>trans('messages.server_error')];
			}
	  }

	  public function updateCustomer($request,$customer){
		try {
			\DB::beginTransaction();
			   if(!$customer)
			   return ["status"=>false, "message"=>trans('messages.data_not_found')];
	
			     $arr = array_merge($request->validated(),['work_days'=>isset($request->work_days) && !empty($request->work_days) ? array_unique(array_map('intval',$request->work_days))  : []]);
				    $customer->update($arr);
				\DB::commit();
				return ["status"=>true, "message"=>trans('messages.success'),'data'=>new CustomerResource($customer)];
			} catch (\Exception $e) {
				\DB::rollback();
				return ["status"=>false, "message"=>trans('messages.server_error')];
			}
	  }

	public function show($customer){

		if(!$customer)
		return ["status"=>false, "message"=>trans('messages.data_not_found')];

		return ["status"=>true, "message"=>trans('messages.success'),'data'=>new CustomerResource($customer)];	
   }

	public function deleteCustomer($customer)
    {
	   try {	
		if(!$customer)
		    return ["status"=>false, "message"=>trans('messages.data_not_found')];

        $customer->delete();
        return ["status"=>true, "message"=>trans('messages.success')];
		 }catch (\Exception $e) {
			return ["status"=>false, "message"=>trans('messages.server_error')];
		}

    }

	protected function getCustomerQuery($user){

	    return ($user->access_all_data) ? Customer::select('customers.*')->join('accounts','accounts.id','=','customers.account_id'): 
				 $user->customers()->join('accounts','accounts.id','=','customers.account_id');
	   
	}


}