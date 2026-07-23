<?php

namespace App\Http\Controllers\API\Panel\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ProfileRequest;
use App\Http\Requests\API\UserRequest;
use App\Http\Resources\API\UserResource;
use App\Http\Resources\API\AdminResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * All queries/writes in this controller are scoped to admin
     * accounts only (users.is_admin = 1).
     */
    public function index(Request $request)
    {
        $limit = $request->filled('per_page') && is_numeric($request->per_page)
            ? $request->per_page
            : 20;

        $admins = User::filter($request)
           // ->where('is_admin', 1)
            ->latest()
            ->paginate($limit);

        return $this->response_api(
            true,
            trans('messages.success'),
            AdminResource::collection($admins)
        );
    }

    public function store(UserRequest $request)
    {
        try {
            $admin = DB::transaction(function () use ($request) {

                $data = array_merge(
                    $request->validated(),
                    [
                        'is_admin' => 1,
                        'access_all_data' => $request->customer_select_all,
                    ]
                );

                $admin = User::create($data);

                if ($request->filled('role_id')) {
                    $admin->syncRoles($request->role_id);
                }

                if (!empty($request->department_ids)) {
                    $admin->departments()->sync($request->department_ids);
                }

                if (!empty($request->branch_ids)) {
                    $admin->branches()->sync($request->branch_ids);
                }

                return $admin;
            });

            return $this->response_api(
                true,
                trans('messages.success'),
                new AdminResource($admin)
            );

        } catch (\Exception $e) {

            Log::error('Admin Store Error', [
                'message' => $e->getMessage()
            ]);

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }

    public function show(User $user)
    {
        if (!$user->is_admin) {
            return $this->response_api(
                false,
                trans('messages.not_found')
            );
        }

        return $this->response_api(
            true,
            trans('messages.success'),
            new UserResource($user)
        );
    }

    public function update(UserRequest $request, User $user)
    {
        if (!$user->is_admin) {
            return $this->response_api(
                false,
                trans('messages.not_found')
            );
        }

        try {

            DB::transaction(function () use ($request, $user) {

                $data = array_merge(
                    $request->validated(),
                    [
                        'is_admin' => 1,
                        'access_all_data' => $request->customer_select_all,
                    ]
                );

                $user->update($data);

                if ($request->filled('role_id')) {
                    $user->syncRoles($request->role_id);
                }

                if (!empty($request->department_ids)) {
                    $user->departments()->sync($request->department_ids);
                }

                if (!empty($request->branch_ids)) {
                    $user->branches()->sync($request->branch_ids);
                }
            });

            return $this->response_api(
                true,
                trans('messages.success'),
                new AdminResource($user->fresh())
            );

        } catch (\Exception $e) {

            Log::error('Admin Update Error', [
                'user_id' => $user->id,
                'message' => $e->getMessage()
            ]);

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }

    public function destroy(User $user)
    {
        if (!$user->is_admin) {
            return $this->response_api(
                false,
                trans('messages.not_found')
            );
        }

        $user->delete();

        return $this->response_api(
            true,
            trans('messages.success')
        );
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

            $user->update($request->validated());

            return $this->response_api(
                true,
                trans('messages.success'),
                new AdminResource($user)
            );

        } catch (\Exception $e) {

            Log::error('Admin Profile Update Error', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage()
            ]);

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }
}