<?php

namespace App\Repository\Interfaces;

use Illuminate\Http\Request;

interface AttendanceReportInterface
{
    public function dailySummary(Request $request): array;

    

     public function showPublicHoliday($id);
     public function createPublicHoliday($request);
     public function updatePublicHoliday($request,$id);
     public function deletePublicHoliday($id);
     public function getPublicHoliday($request);
}