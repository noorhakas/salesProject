<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\Interfaces\VisitInterface;
use App\Repository\VisitScheduleRepository;
use App\Http\Requests\API\VisitRequest;
use App\Http\Exports\DoctorVisitExport;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

class VisitsController extends Controller
{
    public function __construct(
        protected VisitInterface $visitRepository
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Visits
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        return $this->SendResponse(
            $this->visitRepository->getvisitsByPlan($request)
        );
    }


    public function show($id)
    {
        return $this->SendResponse(
            $this->visitRepository->getvisitDtail($id)
        );
    }


    public function currentVisits(Request $request)
    {
        return $this->SendResponse(
            $this->visitRepository->getCurrentVisits($request)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Schedule
    |--------------------------------------------------------------------------
    */

    public function VisitAsSchedule(Request $request)
    {
        $result = (new VisitScheduleRepository())
            ->createSchedule($request);

        return $this->SendResponse([
            'status' => true,
            'message' => trans('messages.success'),
            'data' => $result,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Submit / Create
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
    */

    public function visitCharts(Request $request)
    {
        return $this->SendResponse(
            $this->visitRepository
                ->getVisitCharts($request)
        );
    }


    public function userVisitStatictics(Request $request)
    {
        return $this->SendResponse(
            $this->visitRepository
                ->getUserVisitStatictics($request)
        );
    }


    public function userVisitSalesStatictics(
        Request $request
    ) {
        return $this->SendResponse(
            $this->visitRepository
                ->getUserVisitAndSalesStatictics($request)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | All Visits
    |--------------------------------------------------------------------------
    */

    public function AllVisits(Request $request)
    {
        return $this->SendResponse(
            $this->visitRepository->getAllVisits()
        );
    }


    public function UserVisits(Request $request)
    {
        return $this->SendResponse(
            $this->visitRepository
                ->getVisitsByUserId($request)
        );
    }


    public function getAllUserVisits(Request $request)
    {
        return $this->SendResponse(
            $this->visitRepository
                ->getAllVisitsByUserId($request)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    */

    public function exportUserVisitsToExcel(
        Request $request
    ) {
        $result = $this->visitRepository
            ->getUserVisitStatictics($request);

        $user = User::find(
            $request->user_id
        );

        $repName = $user?->name ?? 'Unknown Rep';

        $startDate = $request->start_date
            ?? now()->toDateString();

        $endDate = $request->end_date
            ?? now()->toDateString();

        $title =
            "Visits for {$repName} on {$startDate} - {$endDate}";

        return Excel::download(
            new DoctorVisitExport(
                $result['data']['by_account'],
                $title
            ),
            'user-visits-by-account.xlsx'
        );
    }
}