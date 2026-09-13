<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Position;
use App\Models\Branch;

class UserSeeder extends Seeder
{
    protected string $password = '123456';

    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Positions
        |--------------------------------------------------------------------------
        */

        $areaManager = Position::updateOrCreate(
            ['ps_key' => 'area_manager'],
            ['name' => 'Area Manager', 'parent_id' => 0]
        );

        $supervisor = Position::updateOrCreate(
            ['ps_key' => 'supervisor'],
            ['name' => 'Supervisor', 'parent_id' => $areaManager->id]
        );

        $salesRep = Position::updateOrCreate(
            ['ps_key' => 'sales_rep'],
            ['name' => 'Sales Representative', 'parent_id' => $supervisor->id]
        );

        /*
        |--------------------------------------------------------------------------
        | Branches must already exist (BranchSeeder runs first) so we can read
        | each branch's real departments and never assign a department to a
        | user that isn't actually available in their branch.
        |--------------------------------------------------------------------------
        */

        $branches = Branch::with('departments')->get();

        if ($branches->isEmpty()) {
            $this->command?->warn('No branches found - run BranchSeeder before UserSeeder.');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Admin - covers every branch, every department that exists in them
        |--------------------------------------------------------------------------
        */

        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'            => 'System Admin',
                'user_name'       => 'admin',
                'password'        => $this->password,
                'phone'           => '01011111111',
                'whatsapp'        => '01011111111',
                'status'          => 1,
                'position'        => $areaManager->id,
                'access_all_data' => 1,
                'manager_id'      => null,
                'is_admin'        => 1,
            ]
        );

        $allBranchIds = $branches->pluck('id')->all();
        $admin->branches()->sync($allBranchIds);
        $admin->departments()->sync($this->departmentIdsForBranches($branches, $allBranchIds));
        $this->syncBranchDepartments($admin, $branches->whereIn('id', $allBranchIds));

        /*
        |--------------------------------------------------------------------------
        | Regional Area Managers - split branches into 2 regions.
        | Each manager's departments = union of departments in their region.
        | First manager: manager@gmail.com (no number). Rest: manager2, manager3...
        |--------------------------------------------------------------------------
        */

        $branchChunks = $branches->chunk((int) ceil($branches->count() / 2))->values();

        $regionManagers = [];

        foreach ($branchChunks as $regionIndex => $regionBranches) {
            $n = $regionIndex + 1;
            $suffix = $n === 1 ? '' : (string) $n;

            $regionBranchNames = $regionBranches->pluck('name')->implode(', ');

            $manager = User::updateOrCreate(
                ['email' => "manager{$suffix}@gmail.com"],
                [
                    'name'            => "Area Manager {$n} ({$regionBranchNames})",
                    'user_name'       => "manager{$suffix}",
                    'password'        => $this->password,
                    'phone'           => '0102' . str_pad((string) $n, 7, '0', STR_PAD_LEFT),
                    'whatsapp'        => '0102' . str_pad((string) $n, 7, '0', STR_PAD_LEFT),
                    'status'          => 1,
                    'position'        => $areaManager->id,
                    'access_all_data' => 1,
                    'manager_id'      => $admin->id,
                ]
            );

            $regionBranchIds = $regionBranches->pluck('id')->all();
            $manager->branches()->sync($regionBranchIds);
            $manager->departments()->sync($this->departmentIdsForBranches($branches, $regionBranchIds));
            $this->syncBranchDepartments($manager, $regionBranches);

            $regionManagers[] = ['manager' => $manager, 'branches' => $regionBranches];
        }

        /*
        |--------------------------------------------------------------------------
        | Supervisors + Sales Reps - one supervisor per (branch, department)
        | pair, plus 3 sales reps under each. This guarantees every branch AND
        | every department-in-that-branch actually has users, nothing empty.
        | Each supervisor reports to their region's manager; each sales rep
        | reports to their own supervisor.
        |
        | IMPORTANT: the "first one has no number" rule is driven by the
        | GLOBAL counters ($supervisorCounter / $salesCounter), not by the
        | department's position inside its branch - otherwise every branch's
        | first department would collide on the same email
        | (supervisor@gmail.com) and overwrite each other via updateOrCreate.
        |--------------------------------------------------------------------------
        */

        $supervisorCounter = 0;
        $salesCounter = 0;

        foreach ($regionManagers as $region) {
            /** @var User $manager */
            $manager = $region['manager'];

            foreach ($region['branches'] as $branch) {
                foreach ($branch->departments as $department) {
                    $supervisorCounter++;
                    $supSuffix = $supervisorCounter === 1 ? '' : (string) $supervisorCounter;

                    $supervisorUser = User::updateOrCreate(
                        ['email' => "supervisor{$supSuffix}@gmail.com"],
                        [
                            'name'            => "Supervisor - {$department->name} ({$branch->name})",
                            'user_name'       => "supervisor{$supSuffix}",
                            'password'        => $this->password,
                            'phone'           => $this->fakePhone('0103', $supervisorCounter),
                            'whatsapp'        => $this->fakePhone('0103', $supervisorCounter),
                            'status'          => 1,
                            'position'        => $supervisor->id,
                            'access_all_data' => 0,
                            'manager_id'      => $manager->id,
                        ]
                    );

                    $supervisorUser->branches()->sync([$branch->id]);
                    $supervisorUser->departments()->sync([$department->id]);
                    $supervisorUser->branchDepartments()->updateOrCreate([
                        'branch_id'     => $branch->id,
                        'department_id' => $department->id,
                    ]);

                    // 3 sales reps under this supervisor, same branch + department
                    for ($i = 1; $i <= 3; $i++) {
                        $salesCounter++;
                        $salesSuffix = $salesCounter === 1 ? '' : (string) $salesCounter;

                        $rep = User::updateOrCreate(
                            ['email' => "sales{$salesSuffix}@gmail.com"],
                            [
                                'name'            => "Sales Rep - {$department->name} ({$branch->name})",
                                'user_name'       => "sales{$salesSuffix}",
                                'password'        => $this->password,
                                'phone'           => $this->fakePhone('0104', $salesCounter),
                                'whatsapp'        => $this->fakePhone('0104', $salesCounter),
                                'status'          => 1,
                                'position'        => $salesRep->id,
                                'access_all_data' => 0,
                                'manager_id'      => $supervisorUser->id,
                            ]
                        );

                        $rep->branches()->sync([$branch->id]);
                        $rep->departments()->sync([$department->id]);
                        $rep->branchDepartments()->updateOrCreate([
                            'branch_id'     => $branch->id,
                            'department_id' => $department->id,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * All department IDs that genuinely exist across the given branch IDs.
     * Always derive a user's departments from this - never a hardcoded id -
     * so it's structurally impossible for a user to be responsible for a
     * department their branch doesn't actually have.
     */
    protected function departmentIdsForBranches($branches, array $branchIds): array
    {
        return $branches
            ->whereIn('id', $branchIds)
            ->pluck('departments')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Populates user_branch_departments for a user that covers MULTIPLE
     * branches (admin / area managers): one row per (branch, department)
     * pair that genuinely exists for each branch in $branchesCollection.
     */
    protected function syncBranchDepartments(User $user, $branchesCollection): void
    {
        foreach ($branchesCollection as $branch) {
            foreach ($branch->departments as $department) {
                $user->branchDepartments()->updateOrCreate([
                    'branch_id'     => $branch->id,
                    'department_id' => $department->id,
                ]);
            }
        }
    }

    /**
     * Deterministic fake phone number so re-running the seeder is stable
     * and no two generated users collide on phone number.
     */
    protected function fakePhone(string $prefix, int $counter): string
    {
        return $prefix . str_pad((string) $counter, 7, '0', STR_PAD_LEFT);
    }
}