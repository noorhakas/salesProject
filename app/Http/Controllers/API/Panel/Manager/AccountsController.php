<?php

namespace App\Http\Controllers\API\Panel\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\Interfaces\AccountInterface;

class AccountsController extends Controller
{
    public $IAccount;

    public function __construct(AccountInterface $IAccount)
    {
        $this->IAccount = $IAccount;
    }

    public function index(Request $request)
    {
        $manager = $request->user();
        $subordinateIds = $manager->getAllSubordinateIds();

        $response = $this->IAccount->getAccountsForManager($request,$subordinateIds);

        return $this->SendResponse($response);
    }


}