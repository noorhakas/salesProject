<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\BranchRequest;
use App\Http\Resources\API\Admin\BranchResource;
use App\Models\Branch;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::with('departments')
            ->when($request->search, function ($q, $v) {
                $q->where('name', 'like', "%{$v}%");
            })
            ->latest()
            ->get();

        return $this->response_api(
            true,
            trans('messages.success'),
            BranchResource::collection($branches)
        );
    }

    public function store(BranchRequest $request)
    {
        \DB::beginTransaction();

        try {
            $branch = Branch::updateOrCreate(
                ['name' => $request->name],
                $request->validated()
            );

            \DB::commit();

            return $this->response_api(
                true,
                trans('messages.success'),
                new BranchResource($branch->load('departments'))
            );
        } catch (\Exception $e) {
            \DB::rollBack();

            return $this->response_api(false, trans('messages.server_error'));
        }
    }

    public function show($id)
    {
        $branch = Branch::with('departments')->find($id);

        if (!$branch) {
            return $this->response_api(false, trans('messages.data_not_found'));
        }

        return $this->response_api(
            true,
            trans('messages.success'),
            new BranchResource($branch)
        );
    }

    public function update(BranchRequest $request, $id)
    {
        \DB::beginTransaction();

        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return $this->response_api(false, trans('messages.data_not_found'));
            }

            $branch->update($request->validated());

            \DB::commit();

            return $this->response_api(
                true,
                trans('messages.success'),
                new BranchResource($branch->load('departments'))
            );
        } catch (\Exception $e) {
            \DB::rollBack();

            return $this->response_api(false, trans('messages.server_error'));
        }
    }

    public function destroy($id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return $this->response_api(false, trans('messages.data_not_found'));
            }

            $branch->delete();

            return $this->response_api(true, trans('messages.success'));
        } catch (\Exception $e) {
            return $this->response_api(false, trans('messages.server_error'));
        }
    }
}