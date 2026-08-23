<?php

namespace App\Http\Controllers\API\Panel\Admin;

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
		
		$role = Role::when(request()->get('search'),fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		     ->get(['id','name','created_at'])->map(fn ($role) => collect($role)
		     ->put('created_at', Carbon::parse($role->created_at)->toDayDateTimeString())
	    );
		return $this->response_api(true,trans('messages.success'),$role);
	}



	public function store(RoleRequest $request)
    {
		
        $role = Role::updateOrCreate(['name'=>$request->name],array_merge($request->validated(),['guard_name'=>'web']));
		$role->syncPermissions($request->permissions);
        return $this->response_api(true, trans('messages.success'));
    }


	public function show($id)
    {
		$role = Role::find($id);
		if(!$role)
		      return $this->response_api(false, trans('messages.data_not_found'));

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
		
		$role = Role::find($id);
		if(!$role)
           return $this->response_api(false, trans('messages.server_error'));

        $role->delete();
        return $this->response_api(true,  trans('messages.success'));
    }


	public function allPermissions()
	{
		$permissions = Permission::selectRaw('id as value, name as label, `group_name`')
        ->get()->groupBy('group_name')
        ->map(function ($items, $group) {
            return [
                'group_name' => $group,
                'children' => $items->map(function ($item) {
                    return [
                        'value' => $item->value,
                        'label' => $item->label,
                    ];
                })->values()
            ];
        })
        ->values(); // Reset keys to be 0-based array

    return $this->response_api(true, trans('messages.success'), $permissions);
	}


	

}