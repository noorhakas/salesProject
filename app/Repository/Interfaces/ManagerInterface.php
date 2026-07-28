<?php

namespace App\Repository\Interfaces;

use App\Models\User;
use Illuminate\Http\Request;

interface ManagerInterface
{
    
    public function managers(Request $request);

    public function managerProfile(Request $request, User $manager);
}