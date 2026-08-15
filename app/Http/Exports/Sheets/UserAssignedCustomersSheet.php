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
                ->with(['account.accType', 'account.class', 'account.brick', 'pharmacyGroup', 'specialty', 'class'])
                ->get();
       // }

        return $customers->map(fn ($customer) => [
            'code'          => $customer->Uuid,
            'group_name'    => optional($customer->pharmacyGroup)->name,
            'account_name'  => optional($customer->account)->name,
            'account_type'  => optional($customer->account?->accType)->name,
            'account_class' => optional($customer->account?->class)->name,
            'doctor_name'   => $customer->name,
            'specialty'     => optional($customer->specialty)->name ?? '',
            'area'          => optional($customer->account?->brick)->name,
            'class'         => optional($customer->class)->name ?? '',
            'phone'         => $customer->phone,
        ]);
    }

    public function headings(): array
    {
        return [
            "CODE", "Group Name", "Account Name", "Account Type",
            "Account Class", "Doctor Name", "Specialty", "Area", "Class", "Phone",
        ];
    }

    public function title(): string
    {
        return 'Customers';
    }

    
}