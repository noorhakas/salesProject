<?php

namespace App\Http\Exports;

use App\Http\Exports\Sheets\AccountsReferenceSheet;
use App\Http\Exports\Sheets\CustomersReferenceSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CustomerExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Accounts'  => new AccountsReferenceSheet(),
            'Customers' => new CustomersReferenceSheet(),
        ];
    }
}