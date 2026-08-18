<?php

namespace App\Http\Controllers\API\Panel\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\Interfaces\CustomerInterface;

class CustomersController extends Controller
{
    public $Icustomer;

    public function __construct(CustomerInterface $Icustomer)
    {
        $this->Icustomer = $Icustomer;
    }

  
    public function index(Request $request)
    {
        $manager = $request->user();

        $subordinateIds = $manager->getAllSubordinateIds();

        $response = $this->Icustomer->getCustomersForManager($request,$subordinateIds);

        return $this->SendResponse($response);
    }

    // public function showClient(
    //     Request $request,
    //     $customerId
    // ) {
    //     $user = $request->user();

    //     $subordinateIds = $user->getAllSubordinateIds();

    //     $response = $this->ICustomer->showCustomer(
    //         $customerId,
    //         $subordinateIds
    //     );

    //     return $this->SendResponse($response);
    // }

}