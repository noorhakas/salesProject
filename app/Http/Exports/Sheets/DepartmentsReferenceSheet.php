<?php

namespace App\Http\Exports\Sheets;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Http\Exports\Concerns\ReferenceSheetStyle;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;

class DepartmentsReferenceSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithEvents
{
    use ReferenceSheetStyle;
    public function query()
    {
        return Department::query()
            ->with('branches:id,name')
            ->orderBy('name');
    }

    public function headings(): array
    {
        return ['Name', 'Branches'];
    }

    public function map($department): array
    {
        return [
            $department->name,
            $department->branches->pluck('name')->implode(', '),
        ];
    }

    public function title(): string
    {
        return 'Departments';
    }

    protected function columns(): array
    {
        return ['A', 'B'];
    }

  
}