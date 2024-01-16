<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\CompanyInterface;
use App\Models\Company;
use App\Http\Resources\API\CompanyResource;

class CompanyRepository implements CompanyInterface
{
      
	  public function getAll($request)
	  {
		$companies = Company::when($request->search,fn($q, $v) =>$q->where('name', 'like', "%{$v}%"))
		               ->orderBy('created_at','Asc')->get();
        $data = CompanyResource::collection($companies);
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }

	  public function createCompany($request){
		
		try {
			\DB::beginTransaction();
			  $company = Company::updateOrCreate(['name'=>$request->name],$request->validated());
			  \DB::commit();
			  return ['status'=>true,'message'=>trans('messages.success'),'data'=>new CompanyResource($company)];
		  } catch (\Exception $e) {
			  \DB::rollback();
			  return ['status'=>false,'message'=>trans('messages.server_error')];
		  }
	  }

	  public function updateCompany($request,$id){

		try {
			\DB::beginTransaction();
			  $company = Company::find($id);
			  if(!$company)
			       return ["status"=>false, "message"=>trans('messages.data_not_found')];
   
			   $company->update($request->validated());
			   \DB::commit();
			   return ["status"=>true, "message"=>trans('messages.success'),'data'=>new CompanyResource($company)];
		   } catch (\Exception $e) {
			  \DB::rollback();
			   return ["status"=>false, "message"=>trans('messages.server_error')];
		   }
	  }

	public function show($id){

		$comapny = Company::find($id);
	   if(!$comapny)
	        return ["status"=>false, "message"=>trans('messages.data_not_found')];

		   return ["status"=>true, "message"=>trans('messages.success'),'data'=>new CompanyResource($comapny)];	   
    }

	public function deleteCompany($id)
    {
		try {	
			$comapny = Company::find($id);
			if(!$comapny)
			return ["status"=>false, "message"=>trans('messages.data_not_found')];
	
			$comapny->delete();
			return ["status"=>true, "message"=>trans('messages.success')];
		 }catch (\Exception $e) {
			return ["status"=>false, "message"=>trans('messages.server_error')];
			}

    }


}