<?php

namespace App\Http\Exports\Sheets;

use App\Models\Branch;
use App\Http\Exports\Concerns\ReferenceSheetStyle;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;

class BranchesReferenceSheet implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithEvents
{
    use ReferenceSheetStyle;

    public function query()
    {
        return Branch::query()->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Name',
            'Address',
            'Phone',
            'Whatsapp',
        ];
    }

    public function map($branch): array
    {
        return [
            $branch->name,
            $branch->address,
            $branch->phone,
            $branch->whatsapp,
        ];
    }

    public function title(): string
    {
        return 'Branches';
    }

    protected function columns(): array
    {
        return [
            'A',
            'B',
            'C',
            'D',
        ];
    }
}