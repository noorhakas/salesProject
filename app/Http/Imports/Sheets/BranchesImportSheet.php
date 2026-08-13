<?php

namespace App\Http\Imports\Sheets;

use App\Models\Branch;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class BranchesImportSheet implements
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
         * New branch
         */
        return Branch::updateOrCreate(
            [
                'name' => $name,
            ],
            [
                'name'     => $name,
                'address'  => $row['address'] ?? null,
                'phone'    => $row['phone'] ?? null,
                'whatsapp' => $row['whatsapp'] ?? null,
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

            'address' => [
                'nullable',
                'string',
                'max:500',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'whatsapp' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }
}