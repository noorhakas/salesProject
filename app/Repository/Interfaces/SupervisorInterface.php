<?php

namespace App\Repository\Interfaces;

use App\Models\User;
use Illuminate\Http\Request;

interface SupervisorInterface
{
    public function statistics(Request $request);

    public function supervisors(Request $request);

    public function supervisorProfile(Request $request, User $supervisor);
}