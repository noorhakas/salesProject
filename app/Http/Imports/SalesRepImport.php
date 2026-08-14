<?php

namespace App\Http\Imports;

use App\Models\User;
use App\Models\Branch;
use App\Models\Department;
use App\Enums\PositionKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class SalesRepImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            foreach ($rows as $index => $row) {

                if (empty($row['name'])) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Find Existing User
                |--------------------------------------------------------------------------
                */

                $user = null;

                if (!empty($row['id'])) {
                    $user = User::where(
                        'emp_no',
                        $row['id']
                    )->first();
                }

                /*
                |--------------------------------------------------------------------------
                | Create / Update
                |--------------------------------------------------------------------------
                */

                if (!$user) {
                    $user = new User();

                    if (!empty($row['id'])) {
                        $user->emp_no = $row['id'];
                    }
                }

                $user->name = trim($row['name']);

                $user->email = !empty($row['email'])
                    ? trim($row['email'])
                    : null;

                $user->phone = !empty($row['phone'])
                    ? trim($row['phone'])
                    : null;

                $user->whatsapp = !empty($row['whatsapp'])
                    ? trim($row['whatsapp'])
                    : null;

                $user->user_name = !empty($row['username'])
                    ? trim($row['username'])
                    : null;

                $user->status = strtolower(
                    trim($row['status'] ?? '')
                ) === 'active' ? 1 : 0;

                /*
                |--------------------------------------------------------------------------
                | Sales Rep Position
                |--------------------------------------------------------------------------
                */

                $position = \App\Models\Position::where(
                    'ps_key',
                    PositionKey::SALES_REP->value
                )->first();

                if ($position) {
                    $user->position = $position->id;
                }

                /*
                |--------------------------------------------------------------------------
                | Password for New User
                |--------------------------------------------------------------------------
                */

                if (!$user->exists) {
                    $user->password = Hash::make(
                        $row['password'] ?? '12345678'
                    );
                }

                $user->save();

                /*
                |--------------------------------------------------------------------------
                | Manager
                |--------------------------------------------------------------------------
                */

                $managerId = $this->resolveManager(
                    $row['manager'] ?? null
                );

                $user->manager_id = $managerId;
                $user->save();

                /*
                |--------------------------------------------------------------------------
                | Branches
                |--------------------------------------------------------------------------
                */

                $branchIds = $this->resolveBranches(
                    $row['branches'] ?? null
                );

                if (method_exists($user, 'branches')) {
                    $user->branches()->sync($branchIds);
                }

                /*
                |--------------------------------------------------------------------------
                | Departments
                |--------------------------------------------------------------------------
                */

                $departmentIds = $this->resolveDepartments(
                    $row['departments'] ?? null
                );

                if (method_exists($user, 'branchDepartments')) {
                    $user->branchDepartments()->sync(
                        $departmentIds
                    );
                }
            }
        });
    }

    protected function resolveManager(?string $value): ?int
    {
        if (!$value) {
            return null;
        }

        $empNo = trim(
            explode('-', $value)[0]
        );

        return User::where(
            'emp_no',
            $empNo
        )->value('id');
    }

    protected function resolveBranches(?string $value): array
    {
        if (!$value) {
            return [];
        }

        $names = collect(
            preg_split('/\s*,\s*/', $value)
        )
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique();

        return Branch::whereIn('name', $names)
            ->pluck('id')
            ->toArray();
    }

    protected function resolveDepartments(?string $value): array
    {
        if (!$value) {
            return [];
        }

        $names = collect(
            preg_split('/\s*,\s*/', $value)
        )
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique();

        return Department::whereIn('name', $names)
            ->pluck('id')
            ->toArray();
    }
}