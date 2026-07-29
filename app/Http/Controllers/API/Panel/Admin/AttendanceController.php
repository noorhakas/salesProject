<?php

namespace App\Http\Controllers\API\Panel\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Interfaces\AttendanceReportInterface;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceReportInterface $attendanceReportRepository
    ) {
    }

    public function dailySummary(Request $request)
    {
        $result = $this->attendanceReportRepository->dailySummary($request);

        return $this->response_api(
            true,
            trans('messages.success'),
            $result
        );
    }
}