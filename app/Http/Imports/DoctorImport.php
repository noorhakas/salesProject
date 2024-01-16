<?php

namespace App\Http\Imports;

use App\Models\Account;
use App\Models\AccType;
use App\Models\Specialty;
use App\Models\Classes;
use App\Models\Bricks;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


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
			 $specailty = trim($row['speciality']);
			 $account_name = trim($row['account_name']);

             $accountData = Account::where('name', 'like', "%{$account_name}%")->first();
			 $accountType = AccType::where('name', 'like', "%{$account_type}%")->first();
			 $specailty = Specialty::where('name', 'like', "%{$specailty}%")->first();
             $bricks = Bricks::where('name','like', "%{$area_name}%")->first();
			 $classData = Classes::where('name','like', "%{$class_name}%")->first();

			 $doctor_name = trim($row['name']);

			 if(!empty($doctor_name) && !empty($accountData) && !empty($specailty))
			 {
				 $account_id = $accountData->id;
				 $account = Customer::updateOrCreate(['name'=>$doctor_name,'account_id'=>$account_id],
				[
					 'name'=>$doctor_name,
					 'brick_id'=>$accountData?$accountData->brick_id:0,
					 'class_id'=>$classData?$classData->id:0,
					 'acc_type_id'=>$accountType?$accountType->id:0,
					 'account_id'=>$account_id,
					 'specialty_id'=>$specailty ? $specailty->id : 0,
					 'phone'=>isset($row['phone']) ? trim($row['phone']) : NULL,
					 'phone1'=>isset($row['phone1']) ? trim($row['phone1']) : NULL,
					 'brief'=>isset($row['brief']) ? trim($row['brief']) : NULL,

				]);
			 }
		}
    }
 public function headingRow(): int
    {
        return 1;
    }
   
}
