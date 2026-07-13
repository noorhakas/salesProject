<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Customer;
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
		$response = $this->Icustomer->getUserCustomer($request);
		return $this->SendResponse($response);
	}

	

	public function show(Customer $customer)
    {
		$response = $this->Icustomer->show($customer);
		return $this->SendResponse($response);
    }

	
}