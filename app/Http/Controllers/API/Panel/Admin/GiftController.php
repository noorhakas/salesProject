<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\GiftRequest;
use App\Repository\Interfaces\GiftInterface;

class GiftController extends Controller
{
    public $IGift;

	  /*  $this->middleware('permission:display Gift')->only(['index', 'show']);
        $this->middleware('permission:create Gift')->only(['store']);
        $this->middleware('permission:update Gift')->only(['update']);
        $this->middleware('permission:delete Gift')->only(['destroy']); */

    public function __construct(GiftInterface $IGift)
    {
        $this->IGift = $IGift;

    
    }

    public function index(Request $request)
    {
        $response = $this->IGift->getAll($request);
        return $this->SendResponse($response);
    }

    public function store(GiftRequest $request)
    {
        $response = $this->IGift->createGift($request);
        return $this->SendResponse($response);
    }

    public function show($id)
    {
        $response = $this->IGift->show($id);
        return $this->SendResponse($response);
    }

    public function update(GiftRequest $request, $id)
    {
        $response = $this->IGift->updateGift($request, $id);
        return $this->SendResponse($response);
    }

    public function destroy($id)
    {
        $response = $this->IGift->deleteGift($id);
        return $this->SendResponse($response);
    }
}