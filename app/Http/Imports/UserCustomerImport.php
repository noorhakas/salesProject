<?php

namespace App\Http\Imports;

use App\Models\Account;
use App\Models\Bricks;
use App\Models\Customer;
use App\Models\Product;
use App\Models\UserProducts;
use App\Models\UserAccounts;
use App\Models\UserBricks;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserCustomerImport implements ToCollection, WithHeadingRow
{
    protected $user_id;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) {
           
          $row = collect($row)->mapWithKeys(function ($value, $key) {
              return [strtolower(str_replace(' ', '_', $key)) => $value];
          });	

            $area_name = trim($row->get('area') ?? '');
            $account_type = trim($row->get('account_type') ?? '');
            $account_name = trim($row->get('account_name') ?? '');
            $doctor_name = trim($row->get('doctor_name') ?? '');
            
            // ??????? ??????? ??????? ?? 10 ??? 14 (K ??? O)
            $productIndexes = [10, 11, 12, 13, 14];
            $productNames = [];
            
            foreach ($productIndexes as $index) {
                $productName = trim($row[$index] ?? '');
                if (!empty($productName)) {
                    $productNames[] = $productName;
                }
            }
            
            $assign = trim($row['assign'] ?? '');
            if (empty($assign)) {
                continue;
            }
    
            $customer_id = null;
    
            // Handle Accounts & Customers
            if (!empty($account_name) && !empty($doctor_name)) {
                $account = Account::join('acc_type', 'acc_type.id', '=', 'accounts.acc_type_id')
                    ->where('accounts.name', 'like', "%{$account_name}%")
                    ->where('acc_type.name', $account_type)
                    ->select('accounts.id', 'accounts.name')
                    ->first();
    
                if ($account) {
                    $customer = Customer::where('name', 'like', "%{$doctor_name}%")
                        ->where('account_id', $account->id)
                        ->first();
    
                    if ($customer) {
                        $customer_id = $customer->id;
    
                        UserAccounts::firstOrCreate([
                            'user_id' => $this->user_id,
                            'customer_id' => $customer_id,
                            'account_id' => $account->id,
                        ]);
                    }
                }
            }
    
            // Handle Products (Requires customer_id)
            if (!empty($productNames) && $customer_id) {
                $products = Product::where(function ($query) use ($productNames) {
                    foreach ($productNames as $productName) {
                       // $query->orWhere('name', 'LIKE', "%{$productName}%");
                       $query->orWhere('name', $productName);
                    }
                })->pluck('id', 'name');
    
                foreach ($products as $productName => $productId) {
                    UserProducts::firstOrCreate([
                        'user_id' => $this->user_id,
                        'customer_id' => $customer_id,
                        'product_id' => $productId,
                    ]);
                }
            }
    
            // Handle Bricks
            if (!empty($area_name)) {
                $brick = Bricks::where('name', 'like', "%{$area_name}%")->first();
    
                if ($brick) {
                    UserBricks::firstOrCreate([
                        'user_id' => $this->user_id,
                        'brick_id' => $brick->id,
                    ]);
                }
            }
        }
    }
    

    public function headingRow(): int
    {
        return 1;
    }
}
?>
