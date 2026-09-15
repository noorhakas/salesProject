<?php

namespace App\Http\Controllers\API\Panel\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ProfileRequest;
use App\Http\Requests\API\AdminRequest;
use App\Http\Resources\API\UserResource;
use App\Http\Resources\API\AdminResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\PaginatesResults;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    use PaginatesResults;

    public function index(Request $request)
    {
        $adminsQuery = User::filter($request)
            ->where('is_admin', 1)
            ->latest();

        $admins = $this->paginateOrAll($adminsQuery, $request);

        return $this->response_api(
            true,
            trans('messages.success'),
            AdminResource::collection($admins)
        );
    }

    /**
     * Create Admin
     */
    public function store(AdminRequest $request)
    {
        try {

            $admin = DB::transaction(function () use ($request) {

                $data = array_merge(
                    $request->validated(),
                    [
                        'is_admin'       => 1,
                        'access_all_data'=> 1,
                        'position'       => 0,
                    ]
                );

               
                $admin = User::create($data);

                if ($request->filled('role_id')) {
                    $role = Role::find($request->role_id);

                    $admin->syncRoles($role);
                }

                // if (!empty($request->department_ids)) {
                //     $admin->departments()->sync($request->department_ids);
                // }

                // if (!empty($request->branch_ids)) {
                //     $admin->branches()->sync($request->branch_ids);
                // }

                return $admin;
            });

            return $this->response_api(
                true,
                trans('messages.success'),
                new AdminResource($admin)
            );

        } catch (\Exception $e) {

            Log::error('Admin Store Error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }

    public function show(User $admin)
    {
        if (!$admin->is_admin) {
            return $this->response_api(
                false,
                trans('messages.not_found')
            );
        }

        return $this->response_api(
            true,
            trans('messages.success'),
            new UserResource($admin)
        );
    }

    
    public function update(AdminRequest $request, User $admin)
    {
        if (!$admin->is_admin) {
            return $this->response_api(
                false,
                trans('messages.not_found')
            );
        }

        try {

            DB::transaction(function () use ($request, $admin) {

                $data = array_merge(
                    $request->validated(),
                    [
                        'is_admin'        => 1,
                        'access_all_data' => 1,
                        'position'        => 0,
                    ]
                );

                $admin->update($data);
                if ($request->filled('role_id')) {
                    $role = Role::findOrFail($request->role_id);

                    $admin->syncRoles($role);
                }

                // if (!empty($request->department_ids)) {
                //     $admin->departments()->sync($request->department_ids);
                // } else {
                //     $admin->departments()->sync([]);
                // }

                // if (!empty($request->branch_ids)) {
                //     $admin->branches()->sync($request->branch_ids);
                // } else {
                //     $admin->branches()->sync([]);
                // }
            });

            return $this->response_api(
                true,
                trans('messages.success'),
                new AdminResource($admin->fresh())
            );

        } catch (\Exception $e) {

            Log::error('Admin Update Error', [
                'user_id' => $admin->id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }

    public function destroy(User $admin)
    {
        if (!$admin->is_admin) {
            return $this->response_api(
                false,
                trans('messages.not_found')
            );
        }

        try {

            $admin->delete();

            return $this->response_api(
                true,
                trans('messages.success')
            );

        } catch (\Exception $e) {

            Log::error('Admin Delete Error', [
                'user_id' => $admin->id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }

    public function myProfile(Request $request)
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            new AdminResource($request->user())
        );
    }

    public function updateProfile(ProfileRequest $request)
    {
        try {

            $user = auth()->user();

            $user->update(
                $request->validated()
            );

            return $this->response_api(
                true,
                trans('messages.success'),
                new AdminResource($user)
            );

        } catch (\Exception $e) {

            Log::error('Admin Profile Update Error', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }
}