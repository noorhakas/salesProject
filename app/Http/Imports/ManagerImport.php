<?php

namespace App\Http\Imports;

use App\Models\User;
use App\Enums\PositionKey;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ManagerImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $manager = User::create([
            'name'      => $row['name'],
            'email'     => $row['email'] ?? null,
            'phone'     => $row['phone'] ?? null,
            'whatsapp'  => $row['whatsapp'] ?? null,
            'user_name' => $row['user_name'] ?? null,
            'password'  => Hash::make($row['password'] ?? str()->random(10)),
            'is_admin'  => 0,
        ]);

        $manager->userposition()->create(['ps_key' => PositionKey::SUPERVISOR->value]);

        if (!empty($row['branch_id'])) {
            $manager->branchDepartments()->create([
                'branch_id'     => $row['branch_id'],
                'department_id' => $row['department_id'] ?? null,
            ]);

            $manager->branches()->sync([$row['branch_id']]);
        }

        return $manager;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|email|unique:users,email',
            'phone'    => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
        ];
    }
}