<?php

namespace App\Http\Controllers\API\Panel\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Repository\Interfaces\AccountInterface;


class AccountController extends Controller
{

	public $IAccount;
    public function __construct(AccountInterface $IAccount)
    {
        $this->IAccount = $IAccount;
    }

	public function index(Request $request)
	{
		$response = $this->IAccount->getUserAccount($request);
		return $this->SendResponse($response);
	}

	
	public function show(Account $account)
    {
		$response = $this->IAccount->show($account);
		return $this->SendResponse($response);
    }

 
}
