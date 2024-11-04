<?php

namespace App\Http\Imports;

use App\Models\Account;
use App\Models\AccType;
use App\Models\Specialty;
use App\Models\Classes;
use App\Models\Bricks;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\PharmacyGroup ;


class DoctorImport implements ToCollection,WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\|null
    
     */
     public function collection(Collection $rows)
    {

	
      foreach ($rows as $row) 
        { 
			//print($row);
			 $area_name = trim($row['area']);
			 $account_type = trim($row['account_type']);
			 $class_name = trim($row['class']);
             $account_class_name = trim($row['account_class']);
			 $specailty = trim($row['specialty']);
			 $account_name = trim($row['account_name']);
             $doctor_name = trim($row['doctor_name']);
             $groupData = '';
             
    
            
            $classData = !empty($class_name) ?  Classes::where('name','like', "{$class_name}")->first() : '';
            $accountclassData = !empty($account_class_name) ?  Classes::where('name','like', "{$account_class_name}")->first() : '';
            $bricks = Bricks::firstOrCreate(['name'=>$area_name]);
            $specailty = Specialty::firstOrCreate(['name'=>$specailty]);
            $accountType = AccType::firstOrCreate(['name'=>$account_type]);

            if(isset($row['group_name']) && !empty($row['group_name']))
              $groupData = PharmacyGroup::firstOrCreate(['name'=>$row['group_name']]);

            if(!empty($account_name)){
                    $accountData = Account::updateOrCreate(['name'=>$account_name],
                    [
                        'name'=>$account_name,
                        'brick_id'=>$bricks?$bricks->id:0,
                        'acc_type_id'=>$accountType?$accountType->id:0,
                        'class_id'=>!empty($accountclassData)?$accountclassData->id:0,
                        'pharmacy_group_id'=>$groupData?$groupData->id:0,
                    ]);
            }


			 if(!empty($doctor_name) && !empty($accountData) && !empty($specailty))
			 {
				 $account_id = $accountData->id;
				 $customer = Customer::updateOrCreate(['name'=>$doctor_name,'account_id'=>$account_id],
				[
					 'name'=>$doctor_name,
					 'brick_id'=>$accountData?$accountData->brick_id:0,
					 'class_id'=>!empty($classData)?$classData->id:0,
					 'acc_type_id'=>$accountType?$accountType->id:0,
					 'account_id'=>$account_id,
					 'specialty_id'=>$specailty ? $specailty->id : 0,
					 'phone'=>isset($row['phone']) ? trim($row['phone']) : NULL,
					 'phone1'=>isset($row['phone1']) ? trim($row['phone1']) : NULL,
					 'brief'=>isset($row['brief']) ? trim($row['brief']) : NULL,

				]);


                if(isset($row['products']) && !empty($row['products'])){
                    $productItems = explode('-', $row['products']);
                    $productIds = Product::whereIn('uuid', $productItems)->pluck('id');
                    $customer->products()->sync($productIds);

                }
			 }
		}
    }
 public function headingRow(): int
    {
        return 1;
    }
   
}
