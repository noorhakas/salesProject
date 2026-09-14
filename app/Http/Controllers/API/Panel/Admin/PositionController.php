<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\PositionRequest;
use App\Repository\Interfaces\AccTypeInterface;
use App\Models\Position;
use App\Http\Resources\API\PositionResource;

class PositionController extends Controller
{
	public $accType;
    public function __construct(AccTypeInterface $accType)
    {
        $this->accType = $accType;
    }

	public function index(Request $request)
	{
		$positions = Position::where('id','!=',3)->when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
                        ->orderBy('created_at','Asc')->get();
      $data = PositionResource::collection($positions);
	   return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
		
	}

	public function store(PositionRequest $request)
    {
		$response = $this->accType->createPosition($request);
		return $this->SendResponse($response);     
    }

	public function show($id)
    {
		$response = $this->accType->showPosition($id);
		return $this->SendResponse($response);
    }

	public function update(PositionRequest $request,$id) {
		
		$response = $this->accType->updatePosition($request,$id);
		return $this->SendResponse($response);
      
	}
	public function destroy($id)
    {
		
		$response = $this->accType->deletePosition($id);
		return $this->SendResponse($response);
	 
    }


}