<?php

namespace App\Repository\Interfaces;

use App\Models\User;
use Illuminate\Http\Request;

interface AttendanceInterface
{
   
    public function storeAttendance(User $user, Request $request);

    public function getTodayAttendanceStatus(User $user): array;

    public function attendanceLog(User $user, Request $request);

    public function show(int $attendanceId): array;

    
}