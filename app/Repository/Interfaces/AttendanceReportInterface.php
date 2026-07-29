<?php

namespace App\Repository\Interfaces;

use Illuminate\Http\Request;

interface AttendanceReportInterface
{
    public function dailySummary(Request $request): array;
}