<?php

namespace App\Exports\sheets;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Collection;

class DepartmentsReferenceSheet implements FromQuery, WithHeadings, WithTitle
{
    public function query()
    {
        return Department::query()
            ->join('branch_departments', 'branch_departments.department_id', '=', 'departments.id')
            ->join('branches', 'branches.id', '=', 'branch_departments.branch_id')
            ->select(
                'departments.id as department_id',
                'departments.name as department_name',
                'branches.id as branch_id',
                'branches.name as branch_name'
            )
            ->orderBy('departments.name')
            ->orderBy('branches.name');
    }

    public function headings(): array
    {
        return ['Department ID', 'Department Name', 'Branch ID', 'Branch Name'];
    }

    public function title(): string
    {
        return 'Departments';
    }
}