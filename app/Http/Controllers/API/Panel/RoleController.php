<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Resources\API\RoleResource;
use App\Http\Requests\API\RoleRequest;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;


class RoleController extends Controller
{
	public function index(Request $request)
	{
		// if (!auth()->user()->hasPermissionTo('display Roles'))
		// 	return $this->response_api(false,__('messages.permission_denied'));
		
		$role = Role::when(request()->get('search'),fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		     ->get(['id','name','created_at'])->map(fn ($role) => collect($role)
		     ->put('created_at', Carbon::parse($role->created_at)->toDayDateTimeString())
	    );
		return $this->response_api(true,trans('messages.success'),$role);
	}



	public function store(RoleRequest $request)
    {
		// if (!auth()->user()->hasPermissionTo('create Role'))
		// 	return $this->response_api(false,__('messages.permission_denied'));

        $role = Role::updateOrCreate(['name'=>$request->name],array_merge($request->validated(),['guard_name'=>'web']));
		$role->syncPermissions($request->permissions);
        return $this->response_api(true, trans('messages.success'));
    }


	public function show($id)
    {
	    // if (!auth()->user()->hasPermissionTo('update Role'))
		// 	return $this->response_api(false,__('messages.permission_denied'));

		$role = Role::find($id);
		if(!$role)
           return $this->response_api(false, trans('messages.server_error'));

	   return $this->response_api(true, trans('messages.success'),new RoleResource($role));
    }

	public function update(RoleRequest $request,Role $role)
    {
		
		if(!$role)
           return $this->response_api(false, trans('messages.server_error'));

             $role->update($request->validated());
		$role->syncPermissions($request->permissions);
        return $this->response_api(true, trans('messages.success'));
    }

	public function destroy($id)
    {
		// if (!auth()->user()->hasPermissionTo('delete Role'))
		// 	return $this->response_api(false,__('messages.permission_denied'));

		$role = Role::find($id);
		if(!$role)
           return $this->response_api(false, trans('messages.server_error'));

        $role->delete();
        return $this->response_api(true,  trans('messages.success'));
    }


	public function allPermissions()
	{
		// if (!auth()->user()->hasAnyPermission(['create Role','update Role']))
		// 	return $this->response_api(false,__('messages.permission_denied'));

		$permissions = Permission::selectraw('id as value ,name as label,created_at')->get();
		return $this->response_api(true,trans('messages.success'),$permissions);
	}



	

}