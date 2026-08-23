<?php

namespace App\Http\Controllers\API\Panel\Admin;

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
		$response = $this->company->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(CompanyRequest $request)
    {
		$response = $this->company->createCompany($request);
		return $this->SendResponse($response);     
    }

	public function show($id)
    {
		$response = $this->company->show($id);
		return $this->SendResponse($response);
    }

	public function update(CompanyRequest $request,$id) {

		$response = $this->company->updateCompany($request,$id);
		return $this->SendResponse($response);
      
	}
	public function destroy($id)
    {
		
		$response = $this->company->deleteCompany($id);
		return $this->SendResponse($response);
	 
    }


}