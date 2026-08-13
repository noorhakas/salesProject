<?php

namespace App\Http\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Http\Exports\Sheets\BranchesReferenceSheet;
use App\Http\Exports\Sheets\DepartmentsReferenceSheet;
use App\Http\Exports\Sheets\SpecialtiesReferenceSheet;
use App\Http\Exports\Sheets\ClassesReferenceSheet;
use App\Http\Exports\Sheets\AccTypeReferenceSheet;
use App\Http\Exports\Sheets\GiftsReferenceSheet;
use App\Http\Exports\Sheets\BricksReferenceSheet;


class SettingsReferenceExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Branches'    => new BranchesReferenceSheet(),
            'Departments' => new DepartmentsReferenceSheet(),
            'Specialties' => new SpecialtiesReferenceSheet(),
            'Classes'     => new ClassesReferenceSheet(),
            'Acc-Types'   => new AccTypeReferenceSheet(),
            'Gifts'       => new GiftsReferenceSheet(),
            'Bricks'      => new BricksReferenceSheet(),
        ];
    }
}