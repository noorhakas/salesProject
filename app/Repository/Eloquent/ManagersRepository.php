<?php

namespace App\Repository\Eloquent;

use App\Repository\Interfaces\ManagerInterface;
use App\Models\User;
use App\Enums\PositionKey;
use Illuminate\Http\Request;
use App\Http\Traits\PaginatesResults;

class ManagersRepository implements ManagerInterface
{
    use PaginatesResults;

    public function managers(Request $request)
    {

        $managerQuey = User::with([
                'userposition',
                'branches:id,name',
                'branchDepartments.branch:id,name',
                'branchDepartments.department:id,name',
            ])
            ->where('is_admin', 0)
            ->whereHas('userposition', fn ($q) =>
                $q->where('ps_key','!=',PositionKey::SALES_REP->value)
            )
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
            )
            ->filter($request)
            ->latest();
        return  $this->paginateOrAll($managerQuey, $request); 
    }

    public function managerProfile(Request $request, User $manager)
    {
        $manager->load([
            'userposition',
            'branches:id,name',
            'branchDepartments.branch:id,name',
            'branchDepartments.department:id,name',
        ]);


        $repQuey = User::with([
                'userposition',
                'branches:id,name',
                'branchDepartments.branch:id,name',
                'branchDepartments.department:id,name',
            ])
            ->whereIn('id', $manager->getAllSubordinateIds())
            ->whereHas('userposition', fn ($q) =>
                $q->where('ps_key', PositionKey::SALES_REP->value)
            )
            ->filter($request)
            ->latest();

        $reps = $this->paginateOrAll($repQuey, $request);    

        return [
            'manager' => $manager,
            'sales_reps' => $reps,
        ];
    }
}