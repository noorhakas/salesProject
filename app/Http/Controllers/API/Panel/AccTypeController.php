<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\AccTypeRequest;
use App\Repository\Interfaces\AccTypeInterface;

class AccTypeController extends Controller
{
	public $accType;
    public function __construct(AccTypeInterface $accType)
    {
        $this->accType = $accType;
    }

	public function index(Request $request)
	{
		$response = $this->accType->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(AccTypeRequest $request)
    {
		$response = $this->accType->createAccType($request);
		return $this->SendResponse($response);     
    }

	public function show($id)
    {
		$response = $this->accType->show($id);
		return $this->SendResponse($response);
    }

	public function update(AccTypeRequest $request,$id) {
		$response = $this->accType->updateAccType($request,$id);
		return $this->SendResponse($response);
      
	}
	public function destroy($id)
    {
		$response = $this->accType->deleteAccType($id);
		return $this->SendResponse($response);
	 
    }


}