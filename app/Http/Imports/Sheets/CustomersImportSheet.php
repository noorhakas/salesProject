<?php

namespace App\Http\Imports\Sheets;

use App\Models\Account;
use App\Models\AccType;
use App\Models\Classes;
use App\Models\Customer;
use App\Models\Specialty;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomersImportSheet implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            if (empty($row['customer_name'])) {
                continue;
            }

            $account = Account::where(
                'name',
                trim($row['account_name'])
            )->first();

            if (!$account) {
                continue;
            }

            $accTypeId = null;

            if (!empty($row['account_type'])) {
                $accTypeId = AccType::where(
                    'name',
                    trim($row['account_type'])
                )->value('id');
            }

            $classId = null;

            if (!empty($row['class'])) {
                $classId = Classes::where(
                    'name',
                    trim($row['class'])
                )->value('id');
            }

            $specialtyId = null;

            if (!empty($row['specialty'])) {
                $specialtyId = Specialty::where(
                    'name',
                    trim($row['specialty'])
                )->value('id');
            }

            Customer::updateOrCreate(
                [
                    'Uuid' => $row['code'] ?? null,
                ],
                [
                    'name'         => trim($row['customer_name']),
                    'account_id'   => $account->id,
                    'acc_type_id'  => $accTypeId,
                    'specialty_id' => $specialtyId,
                    'class_id'     => $classId,
                    'phone'        => $row['phone'] ?? null,
                    'phone1'       => $row['phone_1'] ?? null,
                    'brief'        => $row['brief'] ?? null,
                ]
            );
        }
    }
}