<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Http\Requests\API\CustomerRequest;
use App\Repository\Interfaces\CustomerInterface;
use App\Http\Exports\CustomerExport;
use App\Http\Imports\CustomerImport;
use App\Http\Exports\UserAssignedExport;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{

	public $Icustomer;
    public function __construct(CustomerInterface $Icustomer)
    {
        $this->Icustomer = $Icustomer;
    }

	public function index(Request $request)
	{
		$response = $this->Icustomer->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(CustomerRequest $request)
    {
		$response = $this->Icustomer->createCustomer($request);
		return $this->SendResponse($response);
      
    }

	public function show(Customer $customer)
    {
		
		$response = $this->Icustomer->show($customer);
		return $this->SendResponse($response);
    }

	public function update(CustomerRequest $request,Customer $customer) {

        $response = $this->Icustomer->updateCustomer($request,$customer);
		return $this->SendResponse($response);
	}

	public function destroy(Customer $customer)
    {
		$response = $this->Icustomer->deleteCustomer($customer);
		return $this->SendResponse($response);
    }

   public function FetchAccountAndCustomers(Request $request){
		$response = $this->Icustomer->FetchcustomersAccount($request);
		return $this->SendResponse($response);
	}

	public function exportCustomers(){
        return Excel::download(new CustomerExport(), 'masterlist.xlsx');
    }
	
	 public function importCustomers(Request $request)
    {

        $request->validate([ 'file' => 'required|file|mimes:xls,xlsx' ]);
        $path = $request->file('file');

		try {
			\DB::beginTransaction();
				$doctor = Excel::import(new CustomerImport, $path);
			\DB::commit();
			return  $this->SendResponse(['status'=>true,'message'=>trans('messages.success')]);
		} catch (\Exception $e) {
			\DB::rollback();
				return $this->SendResponse(['status'=>false,'message'=>trans('messages.server_error')]);
		}

    }

public function exportUserAccounts($id){
        $user = User::find($id);
        if(!$user)
            return $this->SendResponse(['status'=>false,'message'=>trans('messages.server_error')]);

        $user_name = $user->user_name;
        return Excel::download(new UserAssignedExport($user), $user_name.'_list.xlsx');
    }

 public function doctorChart(){
        
        $response = $this->Icustomer->getDoctorCharts();
		return $this->SendResponse($response);
    }

}