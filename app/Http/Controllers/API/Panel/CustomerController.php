<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Http\Resources\API\CustomerResource;
use App\Http\Requests\API\CustomerRequest;

class CustomerController extends Controller
{
	public function index(Request $request)
	{
		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		$customers = (clone $this->getListOfCustomers($request))->orderBy('created_at','DESC')->paginate($limit);
		   $data = CustomerResource::collection($customers);
		return $this->response_api(true,trans('messages.success'),$data);
	}

	public function getAllDoctors(Request $request)
	{
		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		$customers = (clone $this->getListOfCustomers($request))->where('acc_type_id',1)->orderBy('created_at','DESC')->paginate($limit);
		   $data = CustomerResource::collection($customers);
		return $this->response_api(true,trans('messages.success'),$data);
	}

	public function getAllAccounts(Request $request)
	{
		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		$customers = (clone $this->getListOfCustomers($request))->where('acc_type_id','!=',1)->orderBy('created_at','DESC')->paginate($limit);
		   $data = CustomerResource::collection($customers);
		return $this->response_api(true,trans('messages.success'),$data);
	}

	protected function getListOfCustomers($request){
		$customers = (auth()->user()->access_all_data) ? Customer::select('customers.*') :  auth()->user()->customers();
		return (Clone $customers)->filter($request);
	}
	public function store(CustomerRequest $request)
    {
	  \DB::beginTransaction();
      try {
			 $customer = Customer::updateOrCreate(['name'=>$request->name],$request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),new CustomerResource($customer));
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
    }

	public function show(Customer $customer)
    {
	   if(!$customer)
           return $this->response_api(false, trans('messages.data_not_found'));

	   return $this->response_api(true, trans('messages.success'),new CustomerResource($customer));
    }

	public function update(CustomerRequest $request,Customer $customer) {
		\DB::beginTransaction();
      try {
		   if(!$customer)
		      return $this->response_api(false, trans('messages.data_not_found'));

			$customer->update($request->validated());
			\DB::commit();
            return $this->response_api(true, trans('messages.success'),new CustomerResource($customer));
		} catch (\Exception $e) {
			\DB::rollback();
			return $this->response_api(false, trans('messages.server_error'));
		}
	}
	public function destroy(Customer $customer)
    {
		if(!$customer)
           return $this->response_api(false, trans('messages.data_not_found'));

        $customer->delete();
        return $this->response_api(true,  trans('messages.success'));
    }


}