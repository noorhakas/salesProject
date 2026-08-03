<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserBranchDepartment;

class UserBranchDepSeeder extends Seeder
{
    /**
     * Safe to run on an already-seeded database. Does NOT touch users,
     * branches, departments, or the old user_branches/user_departments
     * pivots - it only READS from them to figure out the correct
     * (branch, department) pair per user, and INSERTS into the new
     * user_branch_departments table.
     *
     * Logic per user: for every branch the user has, look at which of
     * THAT branch's departments the user is also assigned to (i.e. the
     * intersection), and create one explicit row per valid pair. This
     * naturally gives supervisors/sales reps exactly one row (they only
     * ever have 1 branch + 1 department), and correctly reconstructs
     * multiple rows for admins/managers who cover several branches and
     * departments.
     */
    public function run(): void
    {
        $users = User::with(['branches:id', 'departments:id', 'branches.departments:id'])->get();

        $created = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $userDepartmentIds = $user->departments->pluck('id');

            foreach ($user->branches as $branch) {
                $validDepartmentIds = $branch->departments->pluck('id')
                    ->intersect($userDepartmentIds);

                foreach ($validDepartmentIds as $departmentId) {
                    $row = UserBranchDepartment::firstOrCreate([
                        'user_id'       => $user->id,
                        'branch_id'     => $branch->id,
                        'department_id' => $departmentId,
                    ]);

                    $row->wasRecentlyCreated ? $created++ : $skipped++;
                }
            }
        }

        $this->command?->info("user_branch_departments: {$created} created, {$skipped} already existed.");
    }
}