<?php

namespace App\Http\Controllers\API\Panel\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\SalesRepProfileResource;
use App\Models\User;
use App\Repository\Interfaces\SalesRepInterface;
use Illuminate\Http\Request;

class SalesRepController extends Controller
{
    public function __construct(
        protected SalesRepInterface $salesRepRepository
    ) {
    }

    public function statistics(Request $request)
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            $this->salesRepRepository->statistics($request)
        );
    }

    public function getReps(Request $request)
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            SalesRepProfileResource::collection(
                $this->salesRepRepository->getReps($request)
            )
        );
    }

    public function profile(Request $request, User $salesRep)
    {
        $result = $this->salesRepRepository->profile($request, $salesRep);

        if (! $result['status']) {
            return $this->response_api(false, $result['message']);
        }

        return $this->response_api(
            true,
            trans('messages.success'),
            [
                'sales_rep' => new SalesRepProfileResource($result['sales_rep']),
            ]
        );
    }
}