<?php

namespace App\Http\Imports;

use App\Http\Imports\Sheets\ProductsImportSheet;
use App\Http\Imports\Sheets\CategoriesImportSheet;
use App\Http\Imports\Sheets\CompaniesImportSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductsImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Categories' => new CategoriesImportSheet(),
            'Companies'  => new CompaniesImportSheet(),
			'Products'   => new ProductsImportSheet(),

        ];
    }
}