<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Resources\API\RoleResource;
use App\Http\Requests\API\RoleRequest;
use Carbon\Carbon;

class RoleController extends Controller
{
	public function index()
	{
		$role = Role::get(['id','name','created_at'])->map(fn ($role) => collect($role)
		     ->put('created_at', Carbon::parse($role->created_at)->toDayDateTimeString())
	    );
		return $this->response_api(true,trans('messages.success'),$role);
	}



	public function store(RoleRequest $request)
    {
        $role = Role::updateOrCreate(['name'=>$request->name],$request->validated());
		$role->syncPermissions($request->permissions);
        return $this->response_api(true, trans('messages.success'));
    }


	public function show($id)
    {
		$role = Role::find($id);
		if(!$role)
           return $this->response_api(false, trans('messages.server_error'));

	   return $this->response_api(true, trans('messages.success'),new RoleResource($role));
    }

	public function update(RoleRequest $request,$id)
    {

		$role = Role::find($id);
             $role->update($request->validated());
		$role->syncPermissions($request->permissions);
        return $this->response_api(true, trans('messages.success'));
    }

	public function destroy($id)
    {
		$role = Role::find($id);
		if(!$role)
           return $this->response_api(false, trans('messages.server_error'));

        $role->delete();
        return $this->response_api(true,  trans('messages.success'));
    }


	public function allPermissions()
	{
		$permissions = Permission::get(['id','name','created_at']);
		return $this->response_api(true,trans('messages.success'),$permissions);
	}



	

}