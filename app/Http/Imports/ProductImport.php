<?php

namespace App\Http\Imports;

use App\Models\Product;
use App\Models\Company;
use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class ProductImport implements ToCollection,WithHeadingRow
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
			 $company = trim($row['company']);
			 $category = trim($row['therapeutic_category']);


			 $categoryData = Category::where('name', 'like', "%{$category}%")->first();
			 $companyData = Company::where('name','like', "%{$company}%")->first();

			 $product_name = trim($row['product_name']);

			 if(!empty($product_name))
			 {
				 $account = Product::updateOrCreate(['name'=>$product_name],
				[
					 'name'=>$product_name,
					 'category_id'=>$categoryData?$categoryData->id:0,
					 'company_id'=>$companyData?$companyData->id:0,
					 'description'=>isset($row['description']) ? $row['description'] : NULL,
					 'price'=>isset($row['wsp_kd']) ? trim($row['wsp_kd']) : 0,
					

				]);
			 }
		}
    }
 public function headingRow(): int
    {
        return 1;
    }
   
}
