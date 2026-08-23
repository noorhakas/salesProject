<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\PositionRequest;
use App\Repository\Interfaces\AccTypeInterface;

class PositionController extends Controller
{
	public $accType;
    public function __construct(AccTypeInterface $accType)
    {
        $this->accType = $accType;
    }

	public function index(Request $request)
	{
		$response = $this->accType->getPositionAll($request);
		return $this->SendResponse($response);
	}

	public function store(PositionRequest $request)
    {
		$response = $this->accType->createPosition($request);
		return $this->SendResponse($response);     
    }

	public function show($id)
    {
		$response = $this->accType->showPosition($id);
		return $this->SendResponse($response);
    }

	public function update(PositionRequest $request,$id) {
		
		$response = $this->accType->updatePosition($request,$id);
		return $this->SendResponse($response);
      
	}
	public function destroy($id)
    {
		
		$response = $this->accType->deletePosition($id);
		return $this->SendResponse($response);
	 
    }


}