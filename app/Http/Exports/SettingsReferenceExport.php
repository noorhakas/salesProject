<?php

namespace App\Http\Exports;


use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SettingsReferenceExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Branches'   => new Sheets\BranchesReferenceSheet(),
            'Departments' => new Sheets\DepartmentsReferenceSheet(),
            'Specialties' => new Sheets\SpecialtiesReferenceSheet(),
            'Classes'    => new Sheets\ClassesReferenceSheet(),
        ];
    }
}