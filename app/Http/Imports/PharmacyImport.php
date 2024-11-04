<?php

namespace App\Http\Imports;

use App\Models\Account;
use App\Models\AccType;
use App\Models\Specialty;
use App\Models\PharmacyGroup ;
use App\Models\Bricks;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class PharmacyImport implements ToCollection,WithHeadingRow
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
			 $account_type = trim($row['account_type']);
			 //$class_name = trim($row['class']);
                         $area_name = trim($row['area']);
                         $groups = '';

			 $accountType = AccType::where('name', 'like', "%{$account_type}%")->first();
			 $bricks = Bricks::where('name','like', "%{$area_name}%")->first();

			 $pharmacy_name = trim($row['pharmacy_name']);
                         $group_name = trim($row['group_name']);
                        
                        if(!empty($group_name))   
                         $groups = PharmacyGroup::where('name','like', "%{$group_name}%")->first();

			 if(!empty($pharmacy_name))
			 {
				 $account = Account::updateOrCreate(['name'=>$pharmacy_name],
				[
					 'name'=>$pharmacy_name,
					 'brick_id'=>$bricks?$bricks->id:0,
					 'acc_type_id'=>$accountType?$accountType->id:0,
                                         'pharmacy_group_id'=>$groups?$groups->id:0,
					 'phone'=>isset($row['phone']) ? trim($row['phone']) : NULL,
					 'phone1'=>isset($row['phone1']) ? trim($row['phone1']) : NULL,
					 'address'=>isset($row['address']) ? trim($row['address']) : NULL,
					 'lat'=>isset($row['lat']) ? trim($row['lat']) : NULL,
					 'lng'=>isset($row['lng']) ? trim($row['lng']) : NULL,

				]);
			 }
		}
    }
 public function headingRow(): int
    {
        return 1;
    }
   
}
