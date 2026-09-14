<?php

namespace App\Http\Imports\Sheets;

use App\Models\User;
use App\Models\Account;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserAssignedCustomersImport implements ToCollection, WithHeadingRow
{
    protected User $user;

    public Collection $exist_data;
    public Collection $dontexist_data;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->exist_data = collect();
        $this->dontexist_data = collect();
    }

    public function collection(Collection $rows)
    {
        $matchedCustomerIds = [];

        foreach ($rows as $i => $row) {
            $account_type = trim($row['account_type'] ?? '');
            $account_name = trim($row['account_name'] ?? '');
            $doctor_name  = trim($row['doctor_name'] ?? '');

            if (empty($account_name) || empty($doctor_name)) {
                continue;
            }

            $accountQuery = Account::selectRaw('accounts.id, accounts.name')
                ->join('acc_type', 'acc_type.id', '=', 'accounts.acc_type_id')
                ->where('accounts.name', 'like', "%{$account_name}%");

            if (!empty($account_type)) {
                $accountQuery->where('acc_type.name', $account_type);
            }

            $accountData = $accountQuery->first();

            if (!$accountData) {
                $this->dontexist_data->add([
                    'row'          => $i + 2, // +2: هيدر + index يبدأ من صفر
                    'account_type' => $account_type,
                    'account_name' => $account_name,
                    'doctor_name'  => $doctor_name,
                ]);
                continue;
            }

            $doctorData = Customer::where('name', 'like', "%{$doctor_name}%")
                ->where('account_id', $accountData->id)
                ->first();

            if (!$doctorData) {
                $this->dontexist_data->add([
                    'row'          => $i + 2,
                    'account_type' => $account_type,
                    'account_name' => $account_name,
                    'doctor_name'  => $doctor_name,
                ]);
                continue;
            }

            $matchedCustomerIds[] = $doctorData->id;

            $this->exist_data->add([
                'account_name' => $accountData->name,
                'doctor_name'  => $doctorData->name,
            ]);
        }

        $this->user->customers()->sync(array_unique($matchedCustomerIds));
    }
}