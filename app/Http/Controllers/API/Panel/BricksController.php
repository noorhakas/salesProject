<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\BricksRequest;
use App\Repository\Interfaces\BrickInterface;


class BricksController extends Controller
{
	public $brick;
    public function __construct(BrickInterface $brick)
    {
        $this->brick = $brick;
    }

	public function index(Request $request)
	{
		if (!auth()->user()->hasPermissionTo('display Bricks'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->brick->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(BricksRequest $request)
    {
		if (!auth()->user()->hasPermissionTo('create Brick'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->brick->createBrick($request);
		return $this->SendResponse($response);
    }

	public function show($id)
    {
		if (!auth()->user()->hasPermissionTo('display Bricks'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->brick->show($id);
		return $this->SendResponse($response);
    }

	public function update(BricksRequest $request,$id) {
	
		if (!auth()->user()->hasPermissionTo('update Brick'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->brick->updateBrick($request,$id);
		return $this->SendResponse($response);
	}
	public function destroy($id)
    {
		if (!auth()->user()->hasPermissionTo('delete Brick'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->brick->deleteBrick($id);
		return $this->SendResponse($response);
    }


}