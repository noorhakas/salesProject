<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\PharmacyGroupRequest;
use App\Repository\Interfaces\AccountInterface;


class PharmacyGroupController extends Controller
{
	public $IAccount;
    public function __construct(AccountInterface $IAccount)
    {
        $this->IAccount = $IAccount;
    }

	public function index(Request $request)
	{
	       if (!auth()->user()->hasPermissionTo('display Pharmacy Group'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->IAccount->getAllPharmacyGroups($request);
		return $this->SendResponse($response);
	}

	public function store(PharmacyGroupRequest $request)
    {
		if (!auth()->user()->hasPermissionTo('create Pharmacy Group'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->IAccount->createPharmacyGroup($request);
		return $this->SendResponse($response);
    }

	public function show($id)
    {
		if (!auth()->user()->hasPermissionTo('display Pharmacy Group'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->IAccount->showPharmacyGroup($id);
		return $this->SendResponse($response);
    }

	public function update(PharmacyGroupRequest $request,$id) {
	
		if (!auth()->user()->hasPermissionTo('update Pharmacy Group'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->IAccount->updatePharmacyGroup($request,$id);
		return $this->SendResponse($response);
	}
	public function destroy($id)
    {
		if (!auth()->user()->hasPermissionTo('delete Pharmacy Group'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->IAccount->deletePharmacyGroup($id);
		return $this->SendResponse($response);
    }


}