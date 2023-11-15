<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Http\Requests\API\AccountRequest;
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
		if (!auth()->user()->hasPermissionTo('display Accounts'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->IAccount->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(AccountRequest $request)
    {
		if (!auth()->user()->hasPermissionTo('create Account'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);
		
		$response = $this->IAccount->createAccount($request);
		return $this->SendResponse($response);
      
    }

	public function show(Account $account)
    {
		$response = $this->IAccount->show($account);
		return $this->SendResponse($response);
    }

	public function update(AccountRequest $request,Account $account) {
		if (!auth()->user()->hasPermissionTo('update Account'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

        $response = $this->IAccount->updateAccount($request,$account);
		return $this->SendResponse($response);
	}

	public function destroy(Account $account)
    {
		if (!auth()->user()->hasPermissionTo('delete Account'))
			return $this->SendResponse(["status"=>false, "message"=>__('messages.permission_denied')],403);

		$response = $this->IAccount->deleteAccount($account);
		return $this->SendResponse($response);
    }


}