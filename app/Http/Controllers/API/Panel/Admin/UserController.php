<?php

namespace App\Http\Controllers\API\Panel\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ProfileRequest;
use App\Http\Requests\API\UserRequest;
use App\Http\Imports\UserCustomerImport;
use App\Http\Resources\API\PlansResource;
use App\Http\Resources\API\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->filled('per_page') && is_numeric($request->per_page)
            ? $request->per_page
            : 20;

        $users = User::filter($request)
            ->latest()
            ->paginate($limit);

        return $this->response_api(
            true,
            trans('messages.success'),
            UserResource::collection($users)
        );
    }

    public function managers()
    {
        $managers = User::whereHas('userposition', function ($q) {
            $q->where('parent_id', '!=', 0);
        })
        ->select('id', 'name')
        ->get();

        return $this->response_api(
            true,
            trans('messages.success'),
            $managers
        );
    }

    public function store(UserRequest $request)
    {
        try {
            $user = DB::transaction(function () use ($request) {

                $data = array_merge(
                    $request->validated(),
                    [
                        'access_all_data' => $request->customer_select_all
                    ]
                );

                $user = User::create($data);

                if ($request->type === 'admin' && $request->filled('role_id')) {
                    $user->syncRoles($request->role_id);
                }

				if(!empty($request->department_ids)){
					$user->departments()->sync($request->department_ids);
				}

				if(!empty($request->branch_ids)){
					$user->branches()->sync($request->branch_ids);
				}

                if (
                    $request->type === 'sales'
                    && $request->hasFile('file')
                ) {
                    $filePath = $request->file('file')->store('uploads');

                    Excel::import(
                        new UserCustomerImport($user->id),
                        $filePath
                    );
                }

                return $user;
            });

            return $this->response_api(
                true,
                trans('messages.success'),
                new UserResource($user)
            );

        } catch (\Exception $e) {

            Log::error('User Store Error', [
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
        return $this->response_api(
            true,
            trans('messages.success'),
            new UserResource($user)
        );
    }

    public function update(UserRequest $request, User $user)
    {
        try {

            DB::transaction(function () use ($request, $user) {

                $data = array_merge(
                    $request->validated(),
                    [
                        'access_all_data' => $request->customer_select_all
                    ]
                );

                $user->update($data);

                if ($request->type === 'admin' && $request->filled('role_id')) {
                    $user->syncRoles($request->role_id);
                }

				if(!empty($request->department_ids)){
					$user->departments()->sync($request->department_ids);
				}

				if(!empty($request->branch_ids)){
					$user->branches()->sync($request->branch_ids);
				}

                if (
                    $request->type === 'sales'
                    && $request->hasFile('file')
                ) {
                    $user->bricks()->detach();
                    $user->products()->detach();
                    $user->customers()->detach();

                    $filePath = $request->file('file')->store('uploads');

                    Excel::import(
                        new UserCustomerImport($user->id),
                        $filePath
                    );
                }
            });

            return $this->response_api(
                true,
                trans('messages.success'),
                new UserResource($user->fresh())
            );

        } catch (\Exception $e) {

            Log::error('User Update Error', [
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
            new UserResource($request->user())
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
                new UserResource($user)
            );

        } catch (\Exception $e) {

            Log::error('Profile Update Error', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage()
            ]);

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }

    public function myCurrentPlan()
    {
        $currentPlan = User::getCurrentPlan();

        return $this->response_api(
            true,
            trans('messages.success'),
            $currentPlan
                ? new PlansResource($currentPlan)
                : (object)[]
        );
    }

    public function getPositionList()
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            \App\Enums\UserPositionEnum::toArray()
        );
    }

    public function importUserList(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx',
            'user_id' => 'required|exists:users,id',
        ]);

        try {

            DB::transaction(function () use ($request) {

                $filePath = $request->file('file')->store('uploads');

                Excel::import(
                    new UserCustomerImport($request->user_id),
                    $filePath
                );
            });

            return $this->response_api(
                true,
                trans('messages.success')
            );

        } catch (\Exception $e) {

            Log::error('Import User List Error', [
                'user_id' => $request->user_id,
                'message' => $e->getMessage()
            ]);

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }
}