<?php

namespace App\Repository\Eloquent;

use App\Repository\Interfaces\SupervisorInterface;
use App\Models\User;
use App\Enums\PositionKey;
use App\Services\AttendanceStatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SupervisorRepository implements SupervisorInterface
{
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

        $limit = max((int) $request->input('per_page', 20), 1);

        return User::with([
                'userposition',
                'branches:id,name',
                'departments:id,name',
            ])
            ->where('manager_id', $manager->id)
            ->whereHas('userposition', fn($q) =>
                $q->where('ps_key', PositionKey::SUPERVISOR->value)
            )
            ->when($request->filled('search'), fn($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
            )
            ->filter($request)
            ->latest()
            ->paginate($limit);
    }

    public function supervisorProfile(Request $request, User $supervisor)
    {
        $manager = $request->user();

        if (
            $supervisor->id !== $manager->id &&
            ! in_array($supervisor->id, $manager->getAllSubordinateIds())
        ) {
            return [
                'status' => false,
                'message' => trans('messages.permission_denied'),
            ];
        }

        $supervisor->load([
            'userposition',
            'branches:id,name',
            'departments:id,name',
        ]);

        $limit = max((int) $request->input('per_page', 20), 1);

        $reps = User::with([
                'userposition',
                'branches:id,name',
                'departments:id,name',
            ])
            ->whereIn('id', $supervisor->getAllSubordinateIds())
            ->whereHas('userposition', fn($q) =>
                $q->where('ps_key', PositionKey::SALES_REP->value)
            )
            ->filter($request)
            ->latest()
            ->paginate($limit);

        return [
            'status' => true,
            'supervisor' => $supervisor,
            'sales_reps' => $reps,
        ];
    }
}