<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\CustomerInterface;
use App\Models\Customer;
use App\Models\Account;
use App\Models\User;
use App\Models\Specialty;
use App\Models\AccType;
use App\Http\Resources\API\CustomerResource;
use App\Http\Resources\API\AccountCustomerResource;

class CustomerRepository implements CustomerInterface
{
      
	  public function getAll($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) ? (request()->get('per_page') > 0 ? request()->get('per_page') : 100000) : 20;
		
		
		$customer = $this->getCustomerQuery();
		

		$customers = (clone $customer)->filter($request)->distinct()->orderBy('created_at','DESC')->paginate($limit);
		   $data =CustomerResource::collection($customers);
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }


	  public function getUserCustomer($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) ? (request()->get('per_page') > 0 ? request()->get('per_page') : 100000) : 20;
		
		$customer = $this->getUserCustomerQuery(auth()->user());

		$customers = (clone $customer)->filter($request)->distinct()->orderBy('created_at','DESC')->paginate($limit);
		   $data =CustomerResource::collection($customers);
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }


      public function FetchcustomersAccount($request)
	  {
		$limit = (is_numeric(request()->get('per_page'))) ? (request()->get('per_page') > 0 ? request()->get('per_page') : 100000) : 20;
//->orWhere('customers.name', 'like', "%{$v}%")
                $secondaccounts =Account::selectraw('accounts.id as id ,accounts.name as account_name ,NULL as customer_name,0 as customer_id');
		$accounts =Account::selectraw('accounts.id as id ,accounts.name as account_name ,customers.name as customer_name,customers.id as customer_id')->join('customers','customers.account_id','=','accounts.id');
		$customers = (clone $accounts)       
                           ->when($request->search,fn($q, $v) =>$q->where('accounts.name', 'like', "%{$v}%")->orWhere('customers.name', 'like', "%{$v}%")
)
                  ->union($secondaccounts)->DISTINCT()->paginate($limit);
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

	protected function getCustomerQuery(){
	    return Customer::select('customers.*')->join('accounts','accounts.id','=','customers.account_id');
	}

	protected function getUserCustomerQuery($user){

	    return $user->customers()->select('customers.*')->join('accounts','accounts.id','=','customers.account_id');
	   
	}

  public function getDoctorCharts(){

           $accountData = AccType::get();
           $specialtyData = Specialty::get()->toArray();
            $customers = Customer::select(\DB::raw('specialty.name as specialty_name, acc_type.name as acc_type_name, customers.acc_type_id ,customers.specialty_id,   COUNT(customers.id) as count'))
            ->join('acc_type', 'acc_type.id', '=', 'customers.acc_type_id')
            ->join('specialty', 'specialty.id', '=', 'customers.specialty_id')
            ->groupBy('customers.acc_type_id')
            ->groupBy('customers.specialty_id')
            ->orderBy('customers.acc_type_id', 'asc')
            ->DISTINCT()
            ->get();

        $customerData = $customers->mapWithKeys(function($customer) {  return [$customer->acc_type_id . '-' . $customer->specialty_id => $customer->count];});
        $staticticsData = [];
        $staticticsData = $accountData->map(function($account_type) use ($specialtyData,$customerData) {
                
                return [
                      'name' => $account_type['name'],
                      'specialty_data'=> array_map(function($specialty) use ($customerData,$account_type) {
                        $key = $account_type['id'] . '-' . $specialty['id'];
                        return [
                            'id' => $specialty['id'],
                            'name' => $specialty['name'], 
                            'count' => $customerData->get($key, 0) 
                        ];
                      }, $specialtyData)
                    ];
            });
    
            return ["status"=>true, "message"=>trans('messages.success'),'data'=>$staticticsData];       
   }


}