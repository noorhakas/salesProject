<?php

namespace App\Http\Controllers\API\Panel\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\ManagerResource;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ManagerController extends Controller
{
   

    public function index(Request $request)
    {
        $limit = max((int) $request->input('per_page', 20), 1);

        $managers = User::with([
                'userposition:id,id,ps_key,name',
                'branches:id,name',
                'departments:id,name',
            ])
            ->where('is_admin', 0)
            ->whereHas('userposition', function ($q) {
                $q->where('ps_key', '!=', 'sales_rep');
            })
            ->filter($request)
            ->latest()
            ->paginate($limit);

        return $this->response_api(
            true,
            trans('messages.success'),
            ManagerResource::collection($managers)
        );
    }


}