<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CustomerController extends Controller
{
	public function index()
	{
		return $this->response_api(true,trans('messages.success'));
	}


}