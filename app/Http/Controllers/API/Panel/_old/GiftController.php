<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\GiftRequest;
use App\Repository\Interfaces\GiftInterface;

class GiftController extends Controller
{
	public $IGift;
    public function __construct(GiftInterface $IGift)
    {
        $this->IGift = $IGift;
    }

	public function index(Request $request)
	{
		// if (!auth()->user()->hasPermissionTo('display Acc-Type'))
		// 	return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->IGift->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(GiftRequest $request)
    {
		//if (!auth()->user()->hasPermissionTo('create Acc-Type'))
		//	return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->IGift->createGift($request);
		return $this->SendResponse($response);     
    }

	public function show($id)
    {
		if (!auth()->user()->hasPermissionTo('display Acc-Type'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->IGift->show($id);
		return $this->SendResponse($response);
    }

	public function update(GiftRequest $request,$id) {
		if (!auth()->user()->hasPermissionTo('update Acc-Type'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->IGift->updateGift($request,$id);
		return $this->SendResponse($response);
      
	}
	public function destroy($id)
    {
		if (!auth()->user()->hasPermissionTo('delete Acc-Type'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->accType->deleteAccType($id);
		return $this->SendResponse($response);
	 
    }


}