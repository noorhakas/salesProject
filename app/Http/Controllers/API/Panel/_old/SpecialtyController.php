<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\SpecialtyRequest;
use App\Models\Specialty;
use App\Repository\Interfaces\SpecialtyInterface;

class SpecialtyController extends Controller
{
	public $Ispecialty;
    public function __construct(SpecialtyInterface $Ispecialty)
    {
        $this->Ispecialty = $Ispecialty;
    }

	public function index(Request $request)
	{
		if (!auth()->user()->hasPermissionTo('display Specialty'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);
		$response = $this->Ispecialty->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(SpecialtyRequest $request)
    {
		if (!auth()->user()->hasPermissionTo('create Specialty'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);
		$response = $this->Ispecialty->createSpecialty($request);
		return $this->SendResponse($response); 
      
    }

	public function show(Specialty $specialty)
    {
		if (!auth()->user()->hasPermissionTo('display Specialty'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);
		$response = $this->Ispecialty->show($specialty);
		return $this->SendResponse($response);
    }

	public function update(SpecialtyRequest $request,Specialty $specialty) {
		if (!auth()->user()->hasPermissionTo('update Specialty'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);
		$response = $this->Ispecialty->updateSpecialty($request,$specialty);
		return $this->SendResponse($response);
	}
	public function destroy(Specialty $specialty)
    {
		if (!auth()->user()->hasPermissionTo('delete Specialty'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);
		$response = $this->Ispecialty->deleteSpecialty($specialty);
		return $this->SendResponse($response);
    }


}