<?php

namespace App\Http\Imports;

use App\Models\Account;
use App\Models\AccType;
use App\Models\Bricks;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\PharmacyGroup;

class UserAccountImport implements ToCollection, WithHeadingRow
{
    public $exist_data;
    public $dontexist_data;
    public $exist_brick;
    public $dontexist_brick;
    public $exist_product;
    public $dontexist_product;

    public function __construct()
    {
        $this->exist_data = collect();
        $this->dontexist_data = collect();
        $this->exist_brick = collect();
        $this->dontexist_brick = collect();
        $this->exist_product = collect();
        $this->dontexist_product = collect();
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) 
        { 
            $row = collect($row)->mapWithKeys(function ($value, $key) {
              return [strtolower(str_replace(' ', '_', $key)) => $value];
          });


            $area_name = trim($row->get('area') ?? '');
            $account_type = trim($row->get('account_type') ?? '');
            $account_name = trim($row->get('account_name') ?? '');
            $doctor_name = trim($row->get('doctor_name') ?? '');
            $assign = trim($row['assign'] ?? '');

            // -- Get product names from multiple columns (K to O) --
            $productIndexes = [10, 11, 12, 13, 14]; // ????? K ??? O

            
            $productNames = [];
            foreach ($productIndexes as $column) {
                $productName = trim($row[$column] ?? '');
                if (!empty($productName)) {
                    $productNames[] = $productName;
                }
            }
            
            if (empty($assign)) {
                continue; // Skip if no assign
            }

            if (!empty($productNames)) {
                foreach ($productNames as $product_name) {
                    //$product = Product::where('name', 'like', "%{$product_name}%")->first();
                    $product = Product::where('name', $product_name)->first();

                    if ($product) {
                        if ($this->exist_product->where('id', $product->id)->count() === 0) {
                            $this->exist_product->add([
                                'id' => $product->id,
                                'product_name' => $product->name
                            ]);
                        }
                    } else {
                        if ($this->dontexist_product->where('product_name', $product_name)->count() === 0) {
                            $this->dontexist_product->add([
                                'id' => $i,
                                'product_name' => $product_name
                            ]);
                        }
                    }
                }
            }

            // Brick (Area) Check
            if (!empty($area_name)) {
                $bricks = Bricks::where('name', 'like', "%{$area_name}%")->first();

                if ($bricks) {
                    if ($this->exist_brick->where('id', $bricks->id)->count() === 0) {
                        $this->exist_brick->add([
                            'id' => $bricks->id,
                            'brick_name' => $bricks->name
                        ]);
                    }
                } else {
                    if ($this->dontexist_brick->where('brick_name', $area_name)->count() === 0) {
                        $this->dontexist_brick->add([
                            'id' => $i,
                            'brick_name' => $area_name
                        ]);
                    }
                }
            }

            // Account and Doctor Check
            if (!empty($account_name) && !empty($doctor_name)) {
                $accountData = Account::selectRaw('accounts.id, accounts.name')
                    ->join('acc_type', 'acc_type.id', '=', 'accounts.acc_type_id')
                    ->where('accounts.name', 'like', "%{$account_name}")
                    ->where('acc_type.name', $account_type)
                    ->first();

                if ($accountData) {
                    $account_id = $accountData->id;
                    $doctorData = Customer::selectRaw('customers.account_id, customers.id, customers.name')
                        ->where('name', 'like', "%{$doctor_name}%")
                        ->where('account_id', $account_id)
                        ->first();

                    if ($doctorData) {
                        $this->exist_data->add([
                            'id' => $account_id . '_' . $doctorData->id,
                            'account_name' => $account_name,
                            'doctor_name' => $doctorData->name
                        ]);
                    } else {
                        $this->dontexist_data->add([
                            'id' => $i,
                            'account_name' => $account_name,
                            'doctor_name' => $doctor_name
                        ]);
                    }
                } else {
                    $this->dontexist_data->add([
                        'id' => $i,
                        'account_name' => $account_name,
                        'doctor_name' => $doctor_name
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
