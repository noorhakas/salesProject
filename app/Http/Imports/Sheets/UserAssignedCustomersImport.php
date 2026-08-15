<?php

namespace App\Http\Imports\Sheets;

use App\Models\User;
use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserAssignedCustomersImport implements ToCollection, WithHeadingRow
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function collection(\Illuminate\Support\Collection $rows)
    {
        // بنعتمد على CODE (Uuid) عشان تحديد العميل بشكل دقيق
        $codes = $rows
            ->pluck('code') // heading "CODE" => code
            ->filter()
            ->map(fn ($code) => trim($code))
            ->unique();

        if ($codes->isEmpty()) {
            return;
        }

        $ids = Customer::whereIn('Uuid', $codes)->pluck('id');

        $this->user->customers()->sync($ids);
    }
}