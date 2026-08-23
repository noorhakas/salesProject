<?php

namespace App\Repository\Interfaces;

use App\Models\User;
use Illuminate\Http\Request;

interface SalesRepInterface
{
    public function statistics(Request $request,User $manager);

    public function getReps(Request $request, User $manager);

    public function profile(Request $request, User $salesRep);
}