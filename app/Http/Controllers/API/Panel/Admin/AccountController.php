<?php

namespace App\Http\Controllers\API\Panel\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Http\Requests\API\AccountRequest;
use App\Repository\Interfaces\AccountInterface;
use App\Http\Exports\AccountExport;
use App\Http\Imports\AccountImport;
use App\Http\Imports\UserAssignedImport;
use Maatwebsite\Excel\Facades\Excel;

class AccountController extends Controller
{

	public $IAccount;
    public function __construct(AccountInterface $IAccount)
    {
        $this->IAccount = $IAccount;
    }

	public function index(Request $request)
	{
	
		$response = $this->IAccount->getAll($request);
		return $this->SendResponse($response);
	}

	public function store(AccountRequest $request)
    {
		
		$response = $this->IAccount->createAccount($request);
		return $this->SendResponse($response);
      
    }

	public function show(Account $account)
    {
		$response = $this->IAccount->show($account);
		return $this->SendResponse($response);
    }

	public function update(AccountRequest $request,Account $account) {
		
        $response = $this->IAccount->updateAccount($request,$account);
		return $this->SendResponse($response);
	}

	public function destroy(Account $account)
    {
		$response = $this->IAccount->deleteAccount($account);
		return $this->SendResponse($response);
    }

    public function accountChart(){
        
        $response = $this->IAccount->getAccountCharts();
		return $this->SendResponse($response);
    }


  
    public function importUserAccounts(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx',
        ]);

        $path = $request->file('file');

        try {

            \DB::beginTransaction();

            // User is not created yet
            $account_import = new UserAssignedImport();

            Excel::import($account_import, $path);

            $report = $account_import->report();

            $result = [
                'Exist' => $report['accounts']['matched'],
                'DontExist' => $report['accounts']['unmatched'],

                'BrickExist' => $report['areas']['matched'],
                'DontBrickExist' => $report['areas']['unmatched'],

                'ProductExist' => $report['products']['matched'],
                'DontProductExist' => $report['products']['unmatched'],
            ];

            \DB::commit();

            return $this->SendResponse([
                'status' => true,
                'message' => trans('messages.success'),
                'data' => $result,
            ]);

        } catch (\Exception $e) {

            \DB::rollback();

            return $this->SendResponse([
                'status' => false,
                'message' => trans('messages.server_error'),
                'error' => $e->getMessage(),
            ]);
        }
    }
 
}
