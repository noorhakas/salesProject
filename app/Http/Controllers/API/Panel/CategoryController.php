<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\CategoryRequest;
use App\Repository\Interfaces\CategoryInterface;


class CategoryController extends Controller
{
	public $category;
    public function __construct(CategoryInterface $category)
    {
        $this->category = $category;
    }

	public function index(Request $request)
	{
		if (!auth()->user()->hasPermissionTo('display Category'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->category->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(CategoryRequest $request)
    {
		if (!auth()->user()->hasPermissionTo('create Category'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->category->createCategory($request);
		return $this->SendResponse($response);
    }

	public function show($id)
    {
		if (!auth()->user()->hasPermissionTo('display Category'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->category->show($id);
		return $this->SendResponse($response);
    }

	public function update(CategoryRequest $request,$id) {
	
		if (!auth()->user()->hasPermissionTo('update Category'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->category->updateCategory($request,$id);
		return $this->SendResponse($response);
	}
	public function destroy($id)
    {
		if (!auth()->user()->hasPermissionTo('delete Category'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->category->deleteCategory($id);
		return $this->SendResponse($response);
    }


}