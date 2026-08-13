<?php

namespace App\Http\Imports\Sheets;

use App\Models\Bricks;
use App\Models\Branch;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class BricksImportSheet implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure
{
    use SkipsFailures;

    public function model(array $row)
    {
        $name = trim($row['name'] ?? '');

        if ($name === '') {
            return null;
        }

        /*
         * Branch is provided by name in Excel.
         */
        $branchId = null;

        if (!empty($row['branch'])) {
            $branchId = Branch::query()
                ->where('name', trim($row['branch']))
                ->value('id');
        }

        return Bricks::updateOrCreate(
            [
                'name' => $name,
            ],
            [
                'name'      => $name,
                'branch_id' => $branchId,
            ]
        );
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'branch' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}