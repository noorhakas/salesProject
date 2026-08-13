<?php

namespace App\Http\Imports\Sheets;

use App\Models\Department;
use App\Models\Branch;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class DepartmentsImportSheet implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure
{
    use SkipsFailures;

    /**
     * Import Department
     */
    public function model(array $row)
    {
        $name = trim($row['name'] ?? '');

        if ($name === '') {
            return null;
        }

       
        $department = Department::updateOrCreate(
            [
                'name' => $name,
            ],
            [
                'name' => $name,
            ]
        );

      
        $branchIds = $this->resolveBranchIds(
            $row['branches'] ?? null
        );

        /*
         * Sync branches with department
         */
        $department->branches()->sync($branchIds);

        return $department;
    }

  
    protected function resolveBranchIds($branchesCell): array
    {
        if (
            $branchesCell === null ||
            trim((string) $branchesCell) === ''
        ) {
            return [];
        }

        /*
         * Convert Excel cell to string
         */
        $branchesCell = (string) $branchesCell;

        /*
         * Split names by comma
         */
        $names = explode(',', $branchesCell);

        /*
         * Trim spaces and remove empty values
         */
        $names = array_filter(
            array_map(
                fn ($name) => trim($name),
                $names
            ),
            fn ($name) => $name !== ''
        );

        if (empty($names)) {
            return [];
        }

        
        return Branch::query()
            ->whereIn('name', $names)
            ->pluck('id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Excel validation
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'branches' => [
                'nullable',
                'string',
            ],
        ];
    }
}