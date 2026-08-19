<?php

namespace App\Repository\Interfaces;

use Illuminate\Http\Request;

interface AdminProfileInterface
{
    public function managerProfile($id);
    public function managerSupervisors(Request $request, $id);
    public function managerAccounts(Request $request, $id);
    public function managerCustomers(Request $request, $id);

    public function supervisorProfile($id);
    public function supervisorSalesReps(Request $request, $id);
    public function supervisorAccounts(Request $request, $id);
    public function supervisorCustomers(Request $request, $id);

    public function salesRepProfile($id);
    public function salesRepAccounts(Request $request, $id);
}