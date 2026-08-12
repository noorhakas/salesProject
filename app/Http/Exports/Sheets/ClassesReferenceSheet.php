<?php

namespace App\Http\Exports\Sheets;

use App\Models\Classes;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClassesReferenceSheet implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function query()
    {
        return Classes::query()->orderBy('name');
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Frequency'];
    }

    public function map($class): array
    {
        return [
            $class->id,
            $class->name,
            $class->frequency,
        ];
    }

    public function title(): string
    {
        return 'Classes';
    }
}