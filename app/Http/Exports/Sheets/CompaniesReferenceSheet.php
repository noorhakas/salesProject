<?php

namespace App\Http\Exports\Sheets;

use App\Models\Company;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Http\Exports\Concerns\ReferenceSheetStyle;
use Maatwebsite\Excel\Concerns\WithEvents;


class CompaniesReferenceSheet implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithEvents
{
    use ReferenceSheetStyle;
    public function query()
    {
        return Company::query()
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Name',
        ];
    }

    public function map($company): array
    {
        return [
            $company->name,
        ];
    }

    public function title(): string
    {
        return 'Companies';
    }

}