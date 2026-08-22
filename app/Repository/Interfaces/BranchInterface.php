<?php

namespace App\Repository\Interfaces;

use Illuminate\Http\Request;

interface BranchInterface
{
    public function getBranchesReport(Request $request);

    public function getBranchDetails(Request $request, $branchId);

    public function getBranchProducts(Request $request, $branchId);

    public function getBranchDepartments(Request $request, $branchId);

    public function getBranchSalesReps(Request $request, $branchId);
}