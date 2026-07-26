<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Enums\VisitStatusEnum;
use App\Repository\Interfaces\VisitInterface;
use Carbon\Carbon;

class MapController extends Controller
{
    public function getMaps(Request $request, VisitInterface $IVisit)
    {
        $request->validate([
            'ne_lat'     => 'required|numeric',
            'ne_lng'     => 'required|numeric',
            'sw_lat'     => 'required|numeric',
            'sw_lng'     => 'required|numeric',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate   = $request->end_date ?? Carbon::now()->endOfMonth()->toDateString();

        $accounts = Account::select(['id', 'name', 'lat', 'lng'])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereBetween('lat', [$request->sw_lat, $request->ne_lat])
            ->whereBetween('lng', [$request->sw_lng, $request->ne_lng]);

        $accountIds = (clone $accounts)->pluck('id');

        $visits = (clone $IVisit->DrawVisitCountStatistics())
            ->whereDate('visits.visit_date', '>=', $startDate)
            ->whereDate('visits.visit_date', '<=', $endDate)
            ->where('visits.status', VisitStatusEnum::Visited)
            ->whereIn('visits.account_id', $accountIds)
            ->groupBy('visits.account_id')
            ->get()
            ->keyBy('account_id');

        $locations = $accounts->get()->map(fn ($item) => [
            'id'     => $item->id,
            'name'   => $item->name,
            'lat'    => $item->lat ?? '',
            'lng'    => $item->lng ?? '',
            'visits' => $visits[$item->id]->visit_count ?? 0,
        ]);

        return $this->SendResponse([
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => $locations,
        ]);
    }
}