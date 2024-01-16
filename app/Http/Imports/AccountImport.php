<?php

namespace App\Http\Imports;

use App\Models\Account;
use App\Models\AccType;
use App\Models\Specialty;
use App\Models\Classes;
use App\Models\Bricks;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AccountImport implements ToCollection,WithHeadingRow
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
			 $area_name = trim($row['area']);
			 $account_type = trim($row['account_type']);
			 $class_name = trim($row['class']);
			 $accountType = AccType::where('name', 'like', "%{$account_type}%")->first();
             $bricks = Bricks::where('name','like', "%{$area_name}%")->first();
			 $classData = Classes::where('name','like', "%{$class_name}%")->first();
			 
			 $account_name = $row['account_name'];
			 if(!empty($account_name) && !empty($accountType) && !empty($bricks) )
			 {
				 $account = Account::updateOrCreate(['name'=>$account_name],
				[
					'name'=>$account_name,
					 'brick_id'=>$bricks?$bricks->id:0,
					 'class_id'=>$classData?$classData->id:0,
					 'acc_type_id'=>$accountType?$accountType->id:0,
					 'phone'=>isset($row['phone']) ? trim($row['phone']) : NULL,
					 'phone1'=>isset($row['phone1']) ? trim($row['phone1']) : NULL,
					 'address'=>isset($row['address']) ? trim($row['address']) : NULL,
					 'lat'=>isset($row['lat']) ? trim($row['lat']) : NULL,
					 'lng'=>isset($row['lng']) ? trim($row['lng']) : NULL 

				]);
			 }
		}
    }

 public function headingRow(): int
    {
        return 1;
    }
    // public function rules(): array
    // {
    //     return [
    //         'account_name'=>'required|string|max:100|unique:customers,name,NULL,id,deleted_at,NULL',
	// 		'phone'=>'required|numeric|digits_between:6,14|unique:customers,phone,NULL,id,deleted_at,NULL',
	// 		'phone1'=>'numeric|digits_between:6,14|unique:customers,phone,NULL,id,deleted_at,NULL',
    //     ];
    // }
}
