<?php

namespace App\Http\Controllers\API\Panel\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\SupervisorSimpleResource;
use App\Http\Resources\API\SupervisorResource;

use App\Http\Resources\API\UserDetailResource;
use App\Models\User;
use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    public function supervisors(Request $request)
    {
        $manager = $request->user();

        $limit = max((int) $request->input('per_page', 20), 1);

        $supervisors = User::with('userposition')
            ->where('manager_id', $manager->id)
            ->whereHas('userposition', function ($q) {
                $q->where('ps_key', 'supervisor');
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->filter($request)
            ->latest()
            ->paginate($limit);

        return $this->response_api(true,trans('messages.success'),
            SupervisorSimpleResource::collection($supervisors)
        );
    }

    public function supervisorProfile(Request $request, User $supervisor)
    {
        $manager = $request->user();

        if (
            $supervisor->id !== $manager->id &&
            ! in_array($supervisor->id, $manager->getAllSubordinateIds())
        ) {
            return $this->response_api(
                false,
                trans('messages.permission_denied')
            );
        }

        $limit = max((int) $request->input('per_page', 20), 1);

        $reps = User::with('userposition')
            ->whereIn('id', $supervisor->getAllSubordinateIds())
            ->whereHas('userposition', function ($q) {
                $q->where('ps_key', 'sales_rep');
            })
            ->filter($request)
            ->latest()
            ->paginate($limit);

        return $this->response_api(
            true,
            trans('messages.success'),
            [
                'supervisor' => new SupervisorResource($supervisor),
                'sales_reps' => UserDetailResource::collection($reps),
            ]
        );
    }
}