<?php

namespace App\Http\Controllers\API\Panel\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\Admin\UserDetailResource;
use App\Http\Resources\API\Admin\ManagerResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repository\Interfaces\ManagerInterface;
use App\Http\Exports\ManagersExport;
use App\Http\Imports\ManagerImport;
use Maatwebsite\Excel\Facades\Excel;

class ManagerController extends Controller
{
    public function __construct(
        protected ManagerInterface $managerRepository
    ) {
    }


    public function index(Request $request)
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            ManagerResource::collection(
                $this->managerRepository->managers($request)
            )
        );
    }



    public function show(Request $request, User $manager)
    {
        $result = $this->managerRepository->managerProfile(
            $request,
            $manager
        );

        return $this->response_api(
            true,
            trans('messages.success'),
            [
                'manager' => new ManagerResource($result['manager']),
                'sales_reps' => UserDetailResource::collection(
                    $result['sales_reps']
                ),
            ]
        );
    }

     public function profile(Request $request, User $manager)
    {
        $result = $this->managerRepository->managerProfile(
            $request,
            $manager
        );

        return $this->response_api(
            true,
            trans('messages.success'),
            new ManagerResource($result['manager']),
            
        );
    }


    public function store(Request $request)
    {
        try {
            $manager = DB::transaction(function () use ($request) {
                $data = $request->all();
                $manager = User::create($data);

                if (!empty($request->branch_departments)) {
                    foreach ($request->branch_departments as $item) {
                        $manager->branchDepartments()->create([
                            'branch_id'     => $item['branch_id'],
                            'department_id' => $item['department_id'] ?? null,
                        ]);
                    }

                    // Derive branches from branchDepartments instead of a separate sync
                    $branchIds = collect($request->branch_departments)
                        ->pluck('branch_id')
                        ->unique()
                        ->values()
                        ->toArray();

                    $manager->branches()->sync($branchIds);
                }

                return $manager->load([
                    'branches',
                    'branchDepartments.department',
                    'branchDepartments.branch',
                ]);
            });

            return $this->response_api(true, trans('messages.success'), new ManagerResource($manager));
        } catch (\Exception $e) {
            Log::error('Manager Store Error', ['message' => $e->getMessage()]);
            return $this->response_api(false, trans('messages.server_error'));
        }
    }



    public function update(Request $request, User $manager)
{
    try {
        $manager = DB::transaction(function () use ($request, $manager) {
            $data = $request->all();
            $manager->update($data);

            if (!empty($request->branch_departments)) {
                $branchIds = collect($request->branch_departments)
                    ->pluck('branch_id')
                    ->unique()
                    ->values()
                    ->toArray();

                $manager->branches()->sync($branchIds);
                $manager->branchDepartments()->delete();

                foreach ($request->branch_departments as $item) {
                    $manager->branchDepartments()->create([
                        'branch_id'     => $item['branch_id'],
                        'department_id' => $item['department_id'],
                    ]);
                }
            } else {
                $manager->branches()->detach();
                $manager->branchDepartments()->delete();
            }

            return $manager;
        });

        return $this->response_api(true, trans('messages.success'), new ManagerResource($manager));
    } catch (\Exception $e) {
            Log::error('Manager Store Error', ['message' => $e->getMessage()]);
            return $this->response_api(false, trans('messages.server_error'));
    }
}


    public function destroy(User $manager)
    {

        try {

            $manager->delete();


            return $this->response_api(
                true,
                trans('messages.success')
            );


        } catch (\Exception $e) {


            Log::error('Manager Delete Error', [
                'user_id' => $manager->id,
                'message' => $e->getMessage()
            ]);


            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }


    public function export(Request $request)
    {
        return Excel::download(new ManagersExport($request), 'managers.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx',
        ]);

        try {
            $filePath = $request->file('file')->store('uploads');

            Excel::import(new ManagerImport(), $filePath);

            return $this->response_api(true, trans('messages.success'));

        } catch (\Exception $e) {
            Log::error('Manager Import Error', ['message' => $e->getMessage()]);

            return $this->response_api(false, trans('messages.server_error'));
        }
    }
}