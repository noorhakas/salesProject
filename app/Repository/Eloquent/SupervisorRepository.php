<?php

namespace App\Repository\Eloquent;

use App\Repository\Interfaces\SupervisorInterface;
use App\Models\User;
use App\Enums\PositionKey;
use App\Services\AttendanceStatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Traits\PaginatesResults;


class SupervisorRepository implements SupervisorInterface
{
    use PaginatesResults;
    public function statistics(Request $request)
    {
        $manager = $request->user();

        return app(AttendanceStatusService::class)->statistics(
            User::query()
                ->where('manager_id', $manager->id)
                ->whereHas('userposition', fn($q) =>
                    $q->where('ps_key', PositionKey::SUPERVISOR->value)
                ),
            Carbon::parse($request->date ?? today())
        );
    }

    public function supervisors(Request $request)
    {
        $manager = $request->user();


        $query = User::with([
                'userposition',
                'branches:id,name',
                'branchDepartments.branch:id,name',
                'branchDepartments.department:id,name',
            ])
            ->where('manager_id', $manager->id)
            ->whereHas('userposition', fn($q) =>
                $q->where('ps_key', PositionKey::SUPERVISOR->value)
            )
            ->when($request->filled('search'), fn($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
            )
            ->filter($request)
            ->latest();

       return  $this->paginateOrAll($query, $request);
    }

    public function supervisorProfile(Request $request, User $supervisor)
    {
        $manager = $request->user();

        $supervisor->load([
            'userposition',
            'branches:id,name',
            'branchDepartments.branch:id,name',
            'branchDepartments.department:id,name',
        ]);

        return [
            'status' => true,
            'supervisor' => $supervisor,
        ];
    }

    public function supervisorSalesRep(Request $request, User $supervisor)
    {
        $manager = $request->user();

        if ($supervisor->id !== $manager->id &&! in_array($supervisor->id, $manager->getAllSubordinateIds())) {
            return [
                'status' => false,
                'message' => trans('messages.permission_denied'),
            ];
        }

        $query = User::with([
                'userposition',
                 'branches:id,name',
                'branchDepartments.branch:id,name',
                'branchDepartments.department:id,name',
            ])
            ->whereIn('id', $supervisor->getAllSubordinateIds())
            ->whereHas('userposition', fn($q) =>
                $q->where('ps_key', PositionKey::SALES_REP->value)
            )
            ->filter($request)
            ->latest();

        $reps =  $this->paginateOrAll($query, $request);    

        return [
            'status' => true,
            'sales_reps' => $reps,
        ];
    }
}