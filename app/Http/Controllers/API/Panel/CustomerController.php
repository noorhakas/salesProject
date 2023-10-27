<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Http\Resources\API\CustomerResource;

class CustomerController extends Controller
{
	public function index()
	{
		$limit = $request->per_page??20;
		$customers = Customer::orderBy('created_at','DESC')->paginate($limit);
		   $data = CustomerResource::collection($customers);
		return $this->response_api(true,trans('messages.success'),$data);
	}


}