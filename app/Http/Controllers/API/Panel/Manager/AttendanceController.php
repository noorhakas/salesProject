<?php

namespace App\Http\Controllers\API\Panel\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\UserSimpleResource;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\AttendanceStatusService;
use Carbon\Carbon;

class AttendanceController extends Controller
{
     
   public function index(Request $request)
    {
        $manager = $request->user();

        $date = Carbon::parse($request->date ?? today());
        $position = $request->input('position');

        $query = User::query()->with('userposition');

        if ($position == 'sales_rep') {
            $query->whereIn('users.id', $manager->getAllSubordinateIds());
        } elseif ($position == 'supervisor') {
            $query->where('manager_id', $manager->id);
        } else {
            // لو مفيش position
            $query->where(function ($q) use ($manager) {
                $q->whereIn('users.id', $manager->getAllSubordinateIds())
                ->orWhere('manager_id', $manager->id);
            });
        }

        $query->when($position, function ($q) use ($position) {
            $q->whereHas('userposition', function ($sub) use ($position) {
                $sub->where('ps_key', $position);
            });
        });

    

        $statistics = app(AttendanceStatusService::class)->statistics(clone $query, $date);

        $employees = app(AttendanceStatusService::class)->list(clone $query, $date, $request);

        return $this->response_api(true, trans('messages.success'), [
            'statistics' => $statistics,
            'employees'  => UserSimpleResource::collection($employees),
        ]);
    }

}