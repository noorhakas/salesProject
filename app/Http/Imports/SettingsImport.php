<?php

namespace App\Http\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Http\Imports\Sheets\BranchesImportSheet;
use App\Http\Imports\Sheets\DepartmentsImportSheet;
use App\Http\Imports\Sheets\SpecialtiesImportSheet;
use App\Http\Imports\Sheets\ClassesImportSheet;
use App\Http\Imports\Sheets\AccTypeImportSheet;
use App\Http\Imports\Sheets\GiftsImportSheet;
use App\Http\Imports\Sheets\BricksImportSheet;

class SettingsImport implements WithMultipleSheets
{
    public function sheets(): array
    {
       
        return [
            'Branches' => new BranchesImportSheet(),
            'Departments' => new DepartmentsImportSheet(),
            'Specialties' => new SpecialtiesImportSheet(),
            'Classes' => new ClassesImportSheet(),
            'Acc-Types'  => new AccTypeImportSheet(),
            'Gifts'  => new GiftsImportSheet(),
            'Bricks' => new BricksImportSheet(),
        ];
    }
}