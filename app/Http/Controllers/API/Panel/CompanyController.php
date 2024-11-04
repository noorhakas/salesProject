<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\CompanyRequest;
use App\Repository\Interfaces\CompanyInterface;

class CompanyController extends Controller
{
	public $company;
    public function __construct(CompanyInterface $company)
    {
        $this->company = $company;
    }

	public function index(Request $request)
	{
		//if (!auth()->user()->hasPermissionTo('display Company'))
			//return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->company->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(CompanyRequest $request)
    {
		if (!auth()->user()->hasPermissionTo('create Company'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->company->createCompany($request);
		return $this->SendResponse($response);     
    }

	public function show($id)
    {
		if (!auth()->user()->hasPermissionTo('display Company'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->company->show($id);
		return $this->SendResponse($response);
    }

	public function update(CompanyRequest $request,$id) {
		if (!auth()->user()->hasPermissionTo('update Company'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->company->updateCompany($request,$id);
		return $this->SendResponse($response);
      
	}
	public function destroy($id)
    {
		if (!auth()->user()->hasPermissionTo('delete Company'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->company->deleteCompany($id);
		return $this->SendResponse($response);
	 
    }


}