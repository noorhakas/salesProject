<?php

namespace App\Http\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Http\Exports\Sheets\UserAssignedProductsSheet;
use App\Http\Exports\Sheets\UserAssignedAreasSheet;
use App\Http\Exports\Sheets\UserAssignedCustomersSheet;

class UserAssignedExport implements WithMultipleSheets
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function sheets(): array
    {
        return [
            'Products'  => new UserAssignedProductsSheet($this->user),
            'Areas'     => new UserAssignedAreasSheet($this->user),
            'Customers' => new UserAssignedCustomersSheet($this->user),
        ];
    }
}