<?php

namespace App\Http\Imports;

use App\Models\User;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Http\Imports\Sheets\UserAssignedProductsImport;
use App\Http\Imports\Sheets\UserAssignedAreasImport;
use App\Http\Imports\Sheets\UserAssignedCustomersImport;

class UserAssignedImport implements WithMultipleSheets
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function sheets(): array
    {
        return [
            'Products'  => new UserAssignedProductsImport($this->user),
            'Areas'     => new UserAssignedAreasImport($this->user),
            'Customers' => new UserAssignedCustomersImport($this->user),
        ];
    }
}