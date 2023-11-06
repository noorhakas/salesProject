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
		$response = $this->brick->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(BricksRequest $request)
    {
		$response = $this->brick->createBrick($request);
		return $this->SendResponse($response);
    }

	public function show($id)
    {
		$response = $this->brick->show($id);
		return $this->SendResponse($response);
    }

	public function update(BricksRequest $request,$id) {
	
		$response = $this->brick->updateBrick($request,$id);
		return $this->SendResponse($response);
	}
	public function destroy($id)
    {
		$response = $this->brick->deleteBrick($id);
		return $this->SendResponse($response);
    }


}