<?php

namespace App\Http\Imports\Sheets;

use App\Models\Classes;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class ClassesImportSheet implements
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

        return Classes::updateOrCreate(
            [
                'name' => $name,
            ],
            [
                'name'      => $name,
                'frequency' => $row['frequency'] ?? null,
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

            'frequency' => [
                'nullable',
                'numeric',
            ],
        ];
    }
}