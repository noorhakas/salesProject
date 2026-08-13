<?php

namespace App\Http\Imports\Sheets;

use App\Models\Gift;
use App\Enums\GiftTypeEnum;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class GiftsImportSheet implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure
{
    use SkipsFailures;

    public function model(array $row)
    {
        $name = trim($row['name'] ?? '');
        $typeName = trim($row['type'] ?? '');

        if ($name === '') {
            return null;
        }

        $type = $typeName === 'Leave Behind'
            ? GiftTypeEnum::LeaveBehind
            : GiftTypeEnum::Gift;

        return Gift::updateOrCreate(
            [
                'name' => $name,
            ],
            [
                'name' => $name,
                'type' => $type,
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

            'type' => [
                'required',
                'in:Gift,Leave Behind',
            ],
        ];
    }
}