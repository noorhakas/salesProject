<?php

namespace App\Repository\Interfaces;

use Illuminate\Http\Request;

interface BranchInterface
{
    public function getBranchesReport(Request $request);

    public function getBranchDetails(Request $request, $branchId);
}