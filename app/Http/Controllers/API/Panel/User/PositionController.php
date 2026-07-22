<?php

namespace App\Http\Controllers\API\Panel\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\PositionRequest;
use App\Repository\Interfaces\AccTypeInterface;

class PositionController extends Controller
{
	public $accType;
    public function __construct(AccTypeInterface $accType)
    {
        $this->accType = $accType;
    }

	public function index(Request $request)
	{
		$response = $this->accType->getPositionAll($request);
		return $this->SendResponse($response);
	}

	


}