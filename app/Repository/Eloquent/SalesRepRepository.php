<?php

namespace App\Repository\Eloquent;

use App\Enums\PositionKey;
use App\Models\User;
use App\Repository\Interfaces\SalesRepInterface;
use App\Services\AttendanceStatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesRepRepository implements SalesRepInterface
{
    public function statistics(Request $request)
    {
        $manager = $request->user();

        $subordinateIds = $manager->getAllSubordinateIds();

        return app(AttendanceStatusService::class)->statistics(
            User::query()
                ->whereIn('users.id', $subordinateIds)
                ->whereHas('userposition', fn($q) =>
                    $q->where('ps_key', PositionKey::SALES_REP->value)
                ),
            Carbon::parse($request->date ?? today())
        );
    }

    public function getReps(Request $request)
    {
        $manager = $request->user();

        $subordinateIds = $manager->getAllSubordinateIds();

        $limit = max((int) $request->input('per_page', 20), 1);

        return User::with([
                'userposition',
                'branches:id,name',
                'departments:id,name',
            ])
            ->whereIn('users.id', $subordinateIds)
            ->whereHas('userposition', fn($q) =>
                $q->where('ps_key', PositionKey::SALES_REP->value)
            )
            ->when($request->filled('search'), fn($q) =>
                $q->where('users.name', 'like', '%' . $request->search . '%')
            )
            ->filter($request)
            ->latest('users.created_at')
            ->paginate($limit);
    }

    public function profile(Request $request, User $salesRep)
    {
        $manager = $request->user();

        $subordinateIds = $manager->getAllSubordinateIds();

        print_r($subordinateIds);
        print_r($salesRep->id);
        dd('');
        if (! in_array($salesRep->id, $subordinateIds)) {
            return [
                'status' => false,
                'message' => trans('messages.permission_denied'),
            ];
        }

        $salesRep->load([
            'userposition',
            'branches:id,name',
            'departments:id,name',
            'manager',
        ]);

        return [
            'status' => true,
            'sales_rep' => $salesRep,
        ];
    }
}