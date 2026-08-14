<?php

namespace App\Http\Exports\Sheets;

use App\Http\Exports\Concerns\ReferenceSheetStyle;
use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class CustomersReferenceSheet implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithTitle
{
    use ReferenceSheetStyle;

    public function query()
    {
        return Customer::query()
            ->with([
                'account',
                'accType',
                'class',
                'specialty',
            ])
            ->whereHas('account')
            ->orderBy('customers.account_id');
    }

    public function headings(): array
    {
        return [
            'CODE',
            'Account Name',
            'Account Type',
            'Customer Name',
            'Specialty',
            'Class',
            'Phone',
            'Phone 1',
            'Brief'

        ];
    }

    public function map($customer): array
    {
        return [
            $customer->Uuid,
            optional($customer->account)->name,
            optional($customer->accType)->name,
            $customer->name,
            optional($customer->specialty)->name ?? '',
            optional($customer->class)->name ?? '',
            $customer->phone ?? '',
            $customer->phone_1 ?? '',
            $customer->brief ?? '',
        ];
    }

    public function title(): string
    {
        return 'Customers';
    }

    protected function columns(): array
    {
        return ['A','B','C','D','E','F','G','I','J'];
    }
}