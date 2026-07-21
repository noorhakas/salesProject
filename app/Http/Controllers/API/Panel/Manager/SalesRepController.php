<?php

namespace App\Http\Controllers\API\Panel\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\SalesRepProfileResource;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\AttendanceStatusService;
use Carbon\Carbon;
use App\Enums\PositionKey;

class SalesRepController extends Controller
{
     public function statistics(){

        $manager = $request->user();
        $subordinateIds = $manager->getAllSubordinateIds();

        $statistics = app(AttendanceStatusService::class)->statistics(
            User::query()
                ->whereIn('users.id', $subordinateIds)
                ->whereHas('userposition', fn ($q) => $q->where('ps_key', PositionKey::SALES_REP->value)),
            Carbon::parse($request->date ?? today())
        );

        return $this->response_api(true,trans('messages.success'), $statistics);
    }

    public function getReps(Request $request)
    {
        $manager = $request->user();

        $subordinateIds = $manager->getAllSubordinateIds();

        $limit = max((int) $request->input('per_page', 20), 1);

        $reps = User::with('userposition')
            ->whereIn('users.id', $subordinateIds)
            ->whereHas('userposition', function ($q) {
                $q->where('ps_key', PositionKey::SALES_REP->value);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('users.name', 'like', '%' . $request->search . '%');
            })
            ->filter($request)
            ->latest('users.created_at')
            ->paginate($limit);

        return $this->response_api(
            true,
            trans('messages.success'),
            SalesRepProfileResource::collection($reps)
        );
    }


    public function profile(Request $request, User $salesRep)
    {
        $manager = $request->user();

        $subordinateIds = $manager->getAllSubordinateIds();

        if (! in_array($salesRep->id, $subordinateIds)) {
            return $this->response_api(false, trans('messages.permission_denied'));
        }

        if (optional($salesRep->userposition)->ps_key !== 'sales_rep') {
            return $this->response_api(false,trans('messages.permission_denied'));
        }

        $salesRep->load([
            'userposition',
            'branches:id,name',
            'departments:id,name',
            'manager'
        ]);

        return $this->response_api(
            true,
            trans('messages.success'),
            [
                'sales_rep' => new SalesRepProfileResource($salesRep),
            ]
        );
    }
}