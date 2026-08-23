<?php

namespace App\Repository\Interfaces;

use App\Models\User;
use Illuminate\Http\Request;

interface BranchInterface
{
    public function getBranchesReport(Request $request,?User $user = null);

    public function getBranchDetails(Request $request,$branchId,?User $user = null);

    public function getBranchDepartments(Request $request,$branchId,?User $user = null );

    public function getBranchSalesReps(Request $request,$branchId,?User $user = null);

    public function getBranchProducts(Request $request,$branchId,?User $user = null);
}