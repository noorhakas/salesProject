<?php

namespace App\Http\Imports;

use App\Models\Account;
use App\Models\Customer;
use App\Models\AccType;
use App\Models\Bricks;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class UserAccountImport implements ToCollection,WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\|null
    
     */
    public $data;
    
     public function collection(Collection $rows)
    {

        $ExistData=collect();
        $DontExistData=collect();
        $ExistBrick = collect();
        $DontExistBrick = collect();

      foreach ($rows as $i=>$row) 
        { 
			 $account_name = trim($row['account_name']);
			 $doctor_name =  trim($row['doctor_name']);
			 $account_type = trim($row['account_type']);
             $area_name = rtrim($row['area']);

            if(!empty($area_name)){
                $bricks = Bricks::where('name','like', "%{$area_name}%")->first();

                if($bricks){
                   if($ExistBrick->where('id', $bricks->id)->count() === 0)
                      $ExistBrick->add(['id'=>$bricks?->id ,'brick_name'=>$bricks?->name]);
                }else{
                    if($DontExistBrick->where('brick_name', $area_name)->count() === 0)
                          $DontExistBrick->add(['id'=>$i,'brick_name'=>$area_name]);
                }

            }

           /* if(isset($row['code']) && !empty($row['code'])){

                $doctor_code = rtrim($row['code']);
                $doctorData = Customer::selectRaw('customers.account_id, customers.id , customers.name')->where('customers.Uuid', $doctor_code)->first();

                $doctorData ? $ExistData->add(['id'=>$doctorData?->account_id.'_'.$doctorData?->id ,'account_name'=>$account_name ,'doctor_name'=>$doctorData?->name])
                        : $DontExistData->add(['id'=>$i,'account_name'=>$account_name ,'doctor_name'=>$doctor_name]);
 
            }else*/
{
                
                if(!empty($account_name) && !empty($doctor_name))
                {
                   // $accountTypeData = AccType::where('name',$account_type)->first();
                   // $acc_type_id = $accountTypeData ? $accountTypeData->id : 0;

			     $accountData = Account::selectRaw('accounts.id,accounts.name')->where('accounts.name', 'like', "%{$account_name}")
                    ->join('acc_type','acc_type.id','=','accounts.acc_type_id')->where(['acc_type.name'=>$account_type])->first();

                    if($accountData)
                    {
                        $account_id = $accountData->id;
                        if(!empty($doctor_name)){
                            $doctorData = Customer::selectRaw('customers.account_id, customers.id , customers.name')->where('name', 'like', "%{$doctor_name}%")->where(['account_id'=>$account_id])->first();
                       
 
                        if($doctorData)
                           $ExistData->add(['id'=>$account_id.'_'.$doctorData?->id ,'account_name'=>$account_name ,'doctor_name'=>$doctorData?->name]);
                        else
                           $DontExistData->add(['id'=>$i,'account_name'=>$account_name ,'doctor_name'=>$doctor_name]);
                        }else{
                            $ExistData->add(['id'=>$account_id.'_0','account_name'=>$account_name ,'doctor_name'=>NULL]);

                        }
                   
                    }else{
                           $DontExistData->add(['id'=>$i,'account_name'=>$account_name ,'doctor_name'=>$doctor_name]);
                    }    
			    }

            }

            
        } 

        $this->exist_data = $ExistData;
        $this->dontexist_data = $DontExistData;
        $this->exist_brick = $ExistBrick;
        $this->dontexist_brick = $DontExistBrick;

    }

 public function headingRow(): int
    {
        return 1;
    }
   
}
