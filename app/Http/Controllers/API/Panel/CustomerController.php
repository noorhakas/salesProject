<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Http\Requests\API\CustomerRequest;
use App\Repository\Interfaces\CustomerInterface;

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

	public function getAllDoctors(Request $request)
	{
		$response = $this->Icustomer->getAll($request);
		return $this->SendResponse($response);
	}

	public function getAllAccounts(Request $request)
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


}