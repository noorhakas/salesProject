<?php

namespace App\Http\Controllers\API\Panel\Admin;

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
		if (!auth()->user()->hasPermissionTo('display Classes'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->class->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(ClassesRequest $request)
    { 
		if (!auth()->user()->hasPermissionTo('create Class'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		 $response = $this->class->createClass($request);
		 return $this->SendResponse($response);
    }

	public function show($id)
    {
		if (!auth()->user()->hasPermissionTo('display Classes'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->class->show($id);
		return $this->SendResponse($response);
    }

	public function update(ClassesRequest $request,$id) {
		if (!auth()->user()->hasPermissionTo('update Class'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->class->updateClass($request,$id);
		return $this->SendResponse($response);
		
	}
	public function destroy($id)
    {
		if (!auth()->user()->hasPermissionTo('delete Class'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->class->deleteClass($id);
		return $this->SendResponse($response);
    }


}