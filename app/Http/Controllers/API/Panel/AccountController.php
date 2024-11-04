<?php

namespace App\Http\Controllers\API\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Http\Requests\API\AccountRequest;
use App\Repository\Interfaces\AccountInterface;
use App\Http\Exports\AccountExport;
use App\Http\Exports\PharmacyExport;
use App\Http\Imports\AccountImport;
use App\Http\Imports\PharmacyImport;
use App\Http\Imports\UserAccountImport;
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

    public function accountChart(){
        
        $response = $this->IAccount->getAccountCharts();
		return $this->SendResponse($response);
    }

	public function exportAccounts(){
        return Excel::download(new AccountExport(), 'account.xlsx');
    }

     public function importAccounts(Request $request)
    {

        $request->validate([ 'file' => 'required|file|mimes:xls,xlsx' ]);
        $path = $request->file('file');
		try {
			\DB::beginTransaction();
				$account = Excel::import(new AccountImport, $path);
			\DB::commit();
			return  $this->SendResponse(['status'=>true,'message'=>trans('messages.success')]);
			} catch (\Exception $e) {
				\DB::rollback();
				return $this->SendResponse(['status'=>false,'message'=>trans('messages.server_error')]);
		}

    }


	public function exportPharmacy(){
        return Excel::download(new PharmacyExport(), 'account.xlsx');
    }

     public function importPharmacy(Request $request)
    {

        $request->validate([ 'file' => 'required|file|mimes:xls,xlsx' ]);
        $path = $request->file('file');
		//try {
			//\DB::beginTransaction();
				$pharamcy = Excel::import(new PharmacyImport, $path);
			//\DB::commit();
			//return  $this->SendResponse(['status'=>true,'message'=>trans('messages.success')]);
			//} catch (\Exception $e) {
				//\DB::rollback();
				//return $this->SendResponse(['status'=>false,'message'=>trans('messages.server_error')]);
		//}

    }

  public function importUserAccounts(Request $request)
    {

        $request->validate([ 'file' => 'required|file|mimes:xls,xlsx' ]);
        $path = $request->file('file');
		//try {
		//	\DB::beginTransaction();
                $account_import = new UserAccountImport;
				$data = Excel::import($account_import, $path);
                $result = [ 'Exist'=>$account_import->exist_data,
                            'DontExist'=>$account_import->dontexist_data, 
                            'BrickExist'=>$account_import->exist_brick,
                            'DontBrickExist'=>$account_import->dontexist_brick];
		//	\DB::commit();
		return  $this->SendResponse(['status'=>true,'message'=>trans('messages.success'),'data'=>$result]);
		//} catch (\Exception $e) {
			//	\DB::rollback();
			return $this->SendResponse(['status'=>false,'message'=>trans('messages.server_error')]);
		//}

    }

 

}
