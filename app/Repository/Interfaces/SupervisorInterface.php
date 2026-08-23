<?php

namespace App\Repository\Interfaces;

use App\Models\User;
use Illuminate\Http\Request;

interface SupervisorInterface
{
    public function statistics(Request $request, User $manager);

    public function supervisors(Request $request, User $manager);

    public function supervisorProfile(Request $request, User $supervisor);
    
    public function supervisorSalesRep(Request $request, User $supervisor);

    
}