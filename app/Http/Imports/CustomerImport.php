<?php

namespace App\Http\Imports;

use App\Http\Imports\Sheets\AccountsImportSheet;
use App\Http\Imports\Sheets\CustomersImportSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CustomerImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Accounts'  => new AccountsImportSheet(),
            'Customers' => new CustomersImportSheet(),
        ];
    }
}