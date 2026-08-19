<?php

namespace App\Http\Controllers\API\Panel\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Interfaces\AdminProfileInterface;
use Illuminate\Http\Request;

class ManagerProfController extends Controller
{
    protected AdminProfileInterface $profiles;

    public function __construct(AdminProfileInterface $profiles)
    {
        $this->profiles = $profiles;
    }

    // GET /admin/managers/{id}
    public function show($id)
    {
        $response = $this->profiles->managerProfile($id);

        return $this->response_api($response['status'], $response['message'], $response['data'] ?? null);
    }

    // GET /admin/managers/{id}/supervisors
    public function supervisors(Request $request, $id)
    {
        $response = $this->profiles->managerSupervisors($request, $id);

        return $this->response_api($response['status'], $response['message'], $response['data'] ?? null);
    }

    // GET /admin/managers/{id}/accounts
    public function accounts(Request $request, $id)
    {
        $response = $this->profiles->managerAccounts($request, $id);

        return $this->response_api($response['status'], $response['message'], $response['data'] ?? null);
    }

    // GET /admin/managers/{id}/customers
    public function customers(Request $request, $id)
    {
        $response = $this->profiles->managerCustomers($request, $id);

        return $this->response_api($response['status'], $response['message'], $response['data'] ?? null);
    }
}