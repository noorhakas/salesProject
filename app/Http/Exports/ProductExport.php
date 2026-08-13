<?php

namespace App\Http\Exports;

use App\Http\Exports\Sheets\ProductsReferenceSheet;
use App\Http\Exports\Sheets\CategoriesReferenceSheet;
use App\Http\Exports\Sheets\CompaniesReferenceSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Products'   => new ProductsReferenceSheet(),
            'Categories' => new CategoriesReferenceSheet(),
            'Companies'  => new CompaniesReferenceSheet(),
        ];
    }
}