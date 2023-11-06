<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\CustomerInterface;
use App\Models\Customer;
use App\Http\Resources\API\CustomerResource;

class CustomerRepository implements CustomerInterface
{
      
	  public function getAll($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		$customer =(auth()->user()->access_all_data) ? Customer::select('customers.*') :  auth()->user()->customers();
		$customers = (clone $customer)->filter($request)->orderBy('created_at','DESC')->paginate($limit);
		   $data = CustomerResource::collection($customers);
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }

	  public function createCustomer($request){
		
		try {
			\DB::beginTransaction();
				 $customer = Customer::updateOrCreate(['name'=>$request->name],$request->validated());
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
	
				$customer->update($request->validated());
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


}