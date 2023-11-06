<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Http\Requests\API\ClassesRequest;
use App\Repository\Interfaces\ClassInterFace;

class ClassesController extends Controller
{
    public $class;
    public function __construct(ClassInterFace $class)
    {
        $this->class = $class;
    }

	public function index(Request $request)
	{
		$response = $this->class->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(ClassesRequest $request)
    { 
		 $response = $this->class->createClass($request);
		 return $this->SendResponse($response);
    }

	public function show($id)
    {
		$response = $this->class->show($id);
		return $this->SendResponse($response);
    }

	public function update(ClassesRequest $request,$id) {
		
		$response = $this->class->updateClass($request,$id);
		return $this->SendResponse($response);
		
	}
	public function destroy($id)
    {
		$response = $this->class->deleteClass($id);
		return $this->SendResponse($response);
    }


}