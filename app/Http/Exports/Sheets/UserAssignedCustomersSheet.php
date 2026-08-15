<?php

namespace App\Http\Exports\Sheets;

use App\Models\User;
use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Http\Exports\Concerns\ReferenceSheetStyle;


class UserAssignedCustomersSheet implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    use ReferenceSheetStyle;
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function collection()
    {
        // if ($this->user->access_all_data) {
        //     $customers = Customer::select('customers.*')
        //         ->join('accounts', 'accounts.id', '=', 'customers.account_id')
        //         ->orderBy('customers.account_id')
        //         ->get();
        // } else {
            $customers = $this->user->customers()
                ->with(['account.accType', 'account.class', 'specialty', 'class'])
                ->get();
       // }

        return $customers->map(fn ($customer) => [
            'code'          => $customer->Uuid,
            'account_name'  => optional($customer->account)->name,
            'account_type'  => optional($customer?->accType)->name,
            'customer_name'   => $customer->name,
            'specialty'     => optional($customer->specialty)->name ?? '',
            'class'         => optional($customer->class)->name ?? '',
            'phone'         => $customer->phone,
        ]);
    }

    public function headings(): array
    {
        return [
            "CODE", "Account Name", "Account Type",
             "Customer Name", "Specialty", "Class", "Phone",
        ];
    }

    public function title(): string
    {
        return 'Customers';
    }

     protected function columns(): array
    {
        return ['A','B','C','D','E','F','G'];
    }

    
}