<?php

namespace App\Http\Controllers\API\Panel;

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
		if (!auth()->user()->hasPermissionTo('display Acc-Type'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->accType->getPositionAll($request);
		return $this->SendResponse($response);
	}

	public function store(PositionRequest $request)
    {
		if (!auth()->user()->hasPermissionTo('create Acc-Type'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->accType->createPosition($request);
		return $this->SendResponse($response);     
    }

	public function show($id)
    {
		if (!auth()->user()->hasPermissionTo('display Acc-Type'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->accType->showPosition($id);
		return $this->SendResponse($response);
    }

	public function update(PositionRequest $request,$id) {
		if (!auth()->user()->hasPermissionTo('update Acc-Type'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->accType->updatePosition($request,$id);
		return $this->SendResponse($response);
      
	}
	public function destroy($id)
    {
		if (!auth()->user()->hasPermissionTo('delete Acc-Type'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->accType->deletePosition($id);
		return $this->SendResponse($response);
	 
    }


}