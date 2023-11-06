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
		$response = $this->Ispecialty->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(SpecialtyRequest $request)
    {
		$response = $this->Ispecialty->createSpecialty($request);
		return $this->SendResponse($response); 
      
    }

	public function show(Specialty $specialty)
    {
		$response = $this->Ispecialty->show($specialty);
		return $this->SendResponse($response);
    }

	public function update(SpecialtyRequest $request,Specialty $specialty) {
		
		$response = $this->Ispecialty->updateSpecialty($request,$specialty);
		return $this->SendResponse($response);
	}
	public function destroy(Specialty $specialty)
    {
		$response = $this->Ispecialty->deleteSpecialty($specialty);
		return $this->SendResponse($response);
    }


}