<?php

namespace App\Http\Controllers\API\Panel\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Interfaces\AdminProfileInterface;
use Illuminate\Http\Request;

class SupervisorProfileController extends Controller
{
    protected AdminProfileInterface $profiles;

    public function __construct(AdminProfileInterface $profiles)
    {
        $this->profiles = $profiles;
    }

    // GET /admin/supervisors/{id}
    public function show($id)
    {
        $response = $this->profiles->supervisorProfile($id);

        return $this->response_api($response['status'], $response['message'], $response['data'] ?? null);
    }

    // GET /admin/supervisors/{id}/sales-reps
    public function salesReps(Request $request, $id)
    {
        $response = $this->profiles->supervisorSalesReps($request, $id);

        return $this->response_api($response['status'], $response['message'], $response['data'] ?? null);
    }

    // GET /admin/supervisors/{id}/accounts
    public function accounts(Request $request, $id)
    {
        $response = $this->profiles->supervisorAccounts($request, $id);

        return $this->response_api($response['status'], $response['message'], $response['data'] ?? null);
    }

    // GET /admin/supervisors/{id}/customers
    public function customers(Request $request, $id)
    {
        $response = $this->profiles->supervisorCustomers($request, $id);

        return $this->response_api($response['status'], $response['message'], $response['data'] ?? null);
    }
}