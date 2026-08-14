<?php

namespace App\Http\Exports\Sheets;

use App\Http\Exports\Concerns\ReferenceSheetStyle;
use App\Models\Account;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class AccountsReferenceSheet implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithTitle
{
    use ReferenceSheetStyle;

    public function query()
    {
        return Account::query()
            ->with([
                'brick',
                'accType',
                'class'
            ])
            ->orderBy('id','desc');
    }

    public function headings(): array
    {
        return [
            'Account Name',
            'Account Type',
            'Brick',
            'Class',
            'Phone',
            'Phone 1',
            'Address',
            'Lat',
            'Lng'

        ];
    }

    public function map($account): array
    {
        return [
            $account->name,
            optional($account->accType)->name,
            optional($account->brick)->name,
            optional($account->class)->name,
            $account->phone ?? '',
            $account->phone_1 ?? '',
            $account->address ?? '',
            $account->lat ?? '',
            $account->lng ?? '',
        ];
    }

    public function title(): string
    {
        return 'Accounts';
    }

    protected function columns(): array
    {
        return ['A','B','C','D','E','F','G','H','I'];
    }
}