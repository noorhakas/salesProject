<?php

namespace App\Http\Controllers\API\Panel\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\AttendanceRequest;
use App\Repository\Interfaces\AttendanceInterface;

class AttendanceController extends Controller
{
    protected AttendanceInterface $attendance;

    public function __construct(AttendanceInterface $attendance)
    {
        $this->attendance = $attendance;
    }
//AttendanceRequest
    public function checkIn(AttendanceRequest $request)
    {
        $response = $this->attendance->checkIn(auth()->id(), [
            'lat' => $request->lat,
            'lng' => $request->lng,
        ]);

        return $this->SendResponse($response);
    }

    public function checkOut(AttendanceRequest $request)
    {
        $response = $this->attendance->checkOut(auth()->id(), [
            'lat' => $request->lat,
            'lng' => $request->lng,
        ]);

        return $this->SendResponse($response);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $response = $this->attendance->getUserAttendance($request, auth()->id());
        return $this->SendResponse($response);
    }
}