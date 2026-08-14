<?php

namespace App\Http\Imports\Sheets;

use App\Models\Account;
use App\Models\AccType;
use App\Models\Bricks;
use App\Models\Classes;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AccountsImportSheet implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            if (empty($row['account_name'])) {
                continue;
            }

            $accTypeId = null;

            if (!empty($row['account_type'])) {
                $accTypeId = AccType::where('name', $row['account_type'])->value('id');
            }

            $brickId = null;

            if (!empty($row['brick'])) {
                $brickId = Bricks::where('name', $row['brick'])->value('id');
            }

            $classId = null;

            if (!empty($row['class'])) {
                $classId = Classes::where('name', $row['class'])->value('id');
            }

            Account::updateOrCreate(
                [
                    'name' => trim($row['account_name']),
                ],
                [
                    'acc_type_id' => $accTypeId,
                    'brick_id'    => $brickId,
                    'class_id'    => $classId,
                    'phone'       => $row['phone'] ?? null,
                    'phone1'      => $row['phone_1'] ?? null,
                    'address'     => $row['address'] ?? null,
                    'lat'         => $row['lat'] ?? null,
                    'lng'         => $row['lng'] ?? null,
                ]
            );
        }
    }
}