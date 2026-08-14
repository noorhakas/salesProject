<?php

namespace App\Http\Controllers\API\Panel\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ProfileRequest;
use App\Http\Requests\API\UserRequest;
use App\Http\Imports\UserCustomerImport;
use App\Http\Resources\API\UserResource;
use App\Http\Resources\API\AdminResource;
use App\Enums\PositionKey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Traits\PaginatesResults;
use App\Http\Exports\SalesRepsExport;
use App\Http\Imports\SalesRepImport;

class UserController extends Controller
{
    use PaginatesResults;
     
    public function index(Request $request)
    {
        $userQuery = User::filter($request)->where('is_admin', 0)->whereHas('userposition', fn ($q) =>
					$q->where('ps_key', PositionKey::SALES_REP->value)
				)->latest();

        $users = $this->paginateOrAll($userQuery, $request);    

        return $this->response_api(
            true,
            trans('messages.success'),
            UserResource::collection($users)
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

                if (!empty($request->branch_ids)) {
                    $user->branches()->sync($request->branch_ids);
                }

                if (!empty($request->branch_departments)) {

                    $user->branchDepartments()->delete();

                    foreach ($request->branch_departments as $item) {
                        $user->branchDepartments()->create([
                            'branch_id'     => $item['branch_id'],
                            'department_id' => $item['department_id'],
                        ]);
                    }
                }

                if (
                    $request->type === 'sales'
                    && $request->hasFile('file')
                ) {
                    $request->validate([
                        'file' => 'file|mimes:xls,xlsx',
                    ]);

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

        $user->load([
                'userposition',
                'branches:id,name',
                'branchDepartments.branch:id,name',
                'branchDepartments.department:id,name',
            ]);
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

               if (!empty($request->branch_ids)) {
                    $user->branches()->sync($request->branch_ids);
                }

                $user->branchDepartments()->delete();

                if (!empty($request->branch_departments)) {
                    foreach ($request->branch_departments as $item) {
                        $user->branchDepartments()->create([
                            'branch_id'     => $item['branch_id'],
                            'department_id' => $item['department_id'],
                        ]);
                    }
                }

                if (
                    $request->type === 'sales'
                    && $request->hasFile('file')
                ) {
                    $request->validate([
                        'file' => 'file|mimes:xls,xlsx',
                    ]);

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


    public function exportSalesRep(Request $request)
    {
        return Excel::download(new SalesRepsExport($request), 'salesrep.xlsx');
    }

    public function importSalesRep(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx',
        ]);

        try {
            $filePath = $request->file('file')->store('uploads');

            Excel::import(new SalesRepImport(), $filePath);

            return $this->response_api(true, trans('messages.success'));

        } catch (\Exception $e) {
            Log::error('Manager Import Error', ['message' => $e->getMessage()]);

            return $this->response_api(false, trans('messages.server_error'));
        }
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