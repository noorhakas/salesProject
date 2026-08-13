<?php

namespace App\Http\Exports\Sheets;

use App\Models\Classes;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Http\Exports\Concerns\ReferenceSheetStyle;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;


class ClassesReferenceSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithEvents
{
    use ReferenceSheetStyle;
    public function query()
    {
        return Classes::query()->orderBy('name');
    }

    public function headings(): array
    {
        return ['Name', 'Frequency'];
    }

    public function map($class): array
    {
        return [
            $class->name,
            $class->frequency,
        ];
    }

    public function title(): string
    {
        return 'Classes';
    }

    protected function columns(): array
    {
        return ['A', 'B', 'C'];
    }

 
}