<?php

namespace App\Exports\Sheets;

use App\Models\Branch;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class BranchesReferenceSheet implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function query()
    {
        return Branch::query()->orderBy('name');
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Address', 'Phone', 'Whatsapp'];
    }

    public function map($branch): array
    {
        return [
            $branch->id,
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
}