<?php

namespace App\Http\Controllers\API\Panel\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ProfileRequest;
use App\Http\Requests\API\UserRequest;
use App\Http\Imports\UserAssignedImport;
use App\Http\Resources\API\UserResource;
use App\Http\Resources\API\Admin\UserDetailResource;
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

    /**
     * List Sales Representatives
     */
    public function index(Request $request)
    {
        $userQuery = User::filter($request)
            ->where('is_admin', 0)
            ->whereHas(
                'userposition',
                fn ($q) => $q->where(
                    'ps_key',
                    PositionKey::SALES_REP->value
                )
            )
            ->latest();

        $users = $this->paginateOrAll($userQuery, $request);

        return $this->response_api(
            true,
            trans('messages.success'),
            UserResource::collection($users)
        );
    }


    /**
     * Create User
     */
    public function store(UserRequest $request)
    {
        try {

            $user = DB::transaction(function () use ($request) {

                $data = array_merge(
                    $request->validated(),
                    [
                        'access_all_data' => 0,
                        'is_admin'        => 0,
                        'position'        => 3,
                    ]
                );

                /*
                 * 1. Create User
                 */
                $user = User::create($data);


                /*
                 * 2. Branches
                 */
                $branchIds = collect($request->branch_ids ?? [])
                    ->merge(
                        collect($request->branch_departments ?? [])
                            ->pluck('branch_id')
                    )
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $user->branches()->sync($branchIds);


                /*
                 * 3. Branch Departments
                 */
                if (!empty($request->branch_departments)) {

                    $user->branchDepartments()->delete();

                    foreach ($request->branch_departments as $item) {

                        $user->branchDepartments()->create([
                            'branch_id'     => $item['branch_id'],
                            'department_id' => $item['department_id'],
                        ]);
                    }
                }


                /*
                 * 4. Import User Assignments Excel (Products / Areas / Accounts)
                 */
                if ($request->hasFile('file')) {

                    $request->validate([
                        'file' => 'file|mimes:xls,xlsx',
                    ]);

                    $this->importUserAssignments($user, $request->file('file'));
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
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }


    /**
     * Show User
     */
    public function show(User $user)
    {
        $user->load([
            'userposition',
            'branches:id,name',
            'branchDepartments.branch:id,name',
            'branchDepartments.department:id,name',
            'manager:id,name',
        ]);

        return $this->response_api(
            true,
            trans('messages.success'),
            new UserDetailResource($user)
        );
    }


    /**
     * Update User
     */
    public function update(UserRequest $request, User $user)
    {
        try {

            DB::transaction(function () use ($request, $user) {

                /*
                 * 1. Update basic user data
                 */
                $data = array_merge(
                    $request->validated(),
                    [
                        'access_all_data' => 0,
                        'is_admin'        => 0,
                        'position'        => 3,
                    ]
                );

                $user->update($data);


                $branchIds = collect($request->branch_ids ?? [])
                    ->merge(
                        collect($request->branch_departments ?? [])
                            ->pluck('branch_id')
                    )
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $user->branches()->sync($branchIds);

                $user->branchDepartments()->delete();

                if (!empty($request->branch_departments)) {

                    foreach ($request->branch_departments as $item) {

                        $user->branchDepartments()->create([
                            'branch_id'     => $item['branch_id'],
                            'department_id' => $item['department_id'],
                        ]);
                    }
                }

                if ($request->hasFile('file')) {

                    $request->validate([
                        'file' => 'required|file|mimes:xls,xlsx',
                    ]);

                    $this->importUserAssignments($user, $request->file('file'));
                }
            });


            $user = $user->fresh();


            return $this->response_api(
                true,
                trans('messages.success'),
                new UserResource($user)
            );

        } catch (\Exception $e) {

            Log::error('User Update Error', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }


    /**
     * Delete User
     */
    public function destroy(User $user)
    {
        $user->delete();

        return $this->response_api(
            true,
            trans('messages.success')
        );
    }


    /**
     * My Profile
     */
    public function myProfile(Request $request)
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            new AdminResource($request->user())
        );
    }


    /**
     * Update Profile
     */
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

            Log::error('Profile Update Error', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }


    /**
     * Export Sales Representatives
     */
    public function exportSalesRep(Request $request)
    {
        return Excel::download(
            new SalesRepsExport($request),
            'salesrep.xlsx'
        );
    }


    /**
     * Import Sales Representatives
     */
    public function importSalesRep(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx',
        ]);

        try {

            $filePath = $request->file('file')->store('uploads');

            Excel::import(
                new SalesRepImport(),
                $filePath
            );

            return $this->response_api(
                true,
                trans('messages.success')
            );

        } catch (\Exception $e) {

            Log::error(
                'Manager Import Error',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }


    /**
     * Import / Replace User Assignments
     *
     * Used when assigning a new Excel file
     * to an EXISTING user.
     */
    public function importUserList(Request $request)
    {
        $request->validate([
            'file'    => 'required|file|mimes:xls,xlsx',
            'user_id' => 'required|exists:users,id',
        ]);

        try {

            DB::transaction(function () use ($request) {

                $user = User::findOrFail($request->user_id);

                $this->importUserAssignments($user, $request->file('file'));
            });

            return $this->response_api(
                true,
                trans('messages.success')
            );

        } catch (\Exception $e) {

            Log::error('Import User List Error', [
                'user_id' => $request->user_id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }


    /**
     * Shared logic: import the Products / Areas / Accounts Excel
     * for a given user and apply the sync — used by store(),
     * update() and importUserList() so all three behave identically.
     *
     * - Only syncs a relation if its sheet actually had data rows,
     *   so a partial upload (e.g. only the Accounts sheet) never
     *   wipes existing Products/Areas assignments.
     * - Keeps account_id on the user_customers pivot in sync with
     *   the matched customer.
     */
    protected function importUserAssignments(User $user, $uploadedFile): void
    {
        $accountImport = new UserAssignedImport();

        Excel::import($accountImport, $uploadedFile);

        $report = $accountImport->report();

        /*
         * Products
         */
        if ($report['products']['has_data']) {

            $productIds = collect($report['products']['matched'])
                ->pluck('id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $user->products()->sync($productIds);
        }

        /*
         * Areas / Bricks
         */
        if ($report['areas']['has_data']) {

            $brickIds = collect($report['areas']['matched'])
                ->pluck('id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $user->bricks()->sync($brickIds);
        }

        /*
         * Customers (+ account_id kept on the shared pivot row)
         */
        $customerPivotData = collect($report['accounts']['matched'])
                ->filter(fn ($row) =>
                    !empty($row['customer_id']) &&
                    !empty($row['account_id'])
                )
                ->unique('customer_id')
                ->mapWithKeys(function ($row) {
                    return [
                        $row['customer_id'] => [
                            'account_id' => $row['account_id'],
                        ],
                    ];
                });

            $user->customers()->sync($customerPivotData);
    }
}