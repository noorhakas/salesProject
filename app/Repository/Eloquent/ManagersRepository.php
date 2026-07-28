<?php

namespace App\Repository\Eloquent;

use App\Repository\Interfaces\ManagerInterface;
use App\Models\User;
use App\Enums\PositionKey;
use Illuminate\Http\Request;

class ManagersRepository implements ManagerInterface
{
    public function managers(Request $request)
    {
        $perPageInput = $request->get('per_page');
        $limit = is_numeric($perPageInput)
            ? ($perPageInput > 0 ? (int) $perPageInput : 100000)
            : 20;

        return User::with([
                'userposition',
                'branches:id,name',
                'departments:id,name',
            ])
            ->where('is_admin', 0)
            ->whereHas('userposition', fn ($q) =>
                $q->where('ps_key', PositionKey::SUPERVISOR->value)
            )
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
            )
            ->filter($request)
            ->latest()
            ->paginate($limit);
    }

    public function managerProfile(Request $request, User $manager)
    {
        $manager->load([
            'userposition',
            'branches:id,name',
            'departments:id,name',
        ]);

        $perPageInput = $request->get('per_page');
        $limit = is_numeric($perPageInput)
            ? ($perPageInput > 0 ? (int) $perPageInput : 100000)
            : 20;

        $reps = User::with([
                'userposition',
                'branches:id,name',
                'departments:id,name',
            ])
            ->whereIn('id', $manager->getAllSubordinateIds())
            ->whereHas('userposition', fn ($q) =>
                $q->where('ps_key', PositionKey::SALES_REP->value)
            )
            ->filter($request)
            ->latest()
            ->paginate($limit);

        return [
            'manager' => $manager,
            'sales_reps' => $reps,
        ];
    }
}