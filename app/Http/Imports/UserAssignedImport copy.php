<?php

namespace App\Http\Imports;

use App\Models\User;
use App\Http\Imports\Sheets\UserAssignedProductsImport;
use App\Http\Imports\Sheets\UserAssignedAreasImport;
use App\Http\Imports\Sheets\UserAssignedCustomersImport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UserAssignedImport implements WithMultipleSheets
{
    protected $user;

    protected UserAssignedProductsImport $productsSheet;
    protected UserAssignedAreasImport $areasSheet;
    protected UserAssignedCustomersImport $accountsSheet;

    public function __construct(User $user)
    {
        $this->user = $user;

        $this->productsSheet = new UserAssignedProductsImport($user);
        $this->areasSheet    = new UserAssignedAreasImport($user);
        $this->accountsSheet = new UserAssignedCustomersImport($user);
    }

    public function sheets(): array
    {
        return [
            'Products' => $this->productsSheet,
            'Areas'    => $this->areasSheet,
            'Accounts' => $this->accountsSheet,
        ];
    }

    public function report(): array
    {
        return [
            'products' => [
                'matched'    => $this->productsSheet->exist_product,
                'unmatched'  => $this->productsSheet->dontexist_product,
            ],
            'areas' => [
                'matched'    => $this->areasSheet->exist_brick,
                'unmatched'  => $this->areasSheet->dontexist_brick,
            ],
            'accounts' => [
                'matched'    => $this->accountsSheet->exist_data,
                'unmatched'  => $this->accountsSheet->dontexist_data,
            ],
        ];
    }
}