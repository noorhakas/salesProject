<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class AssignPermissionsToUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // user رقم 1
        $user = User::find(1);

        if (!$user) {
            $this->command->error('User not found!');
            return;
        }

        // كل البرمشنز
        $permissions = Permission::all();

        // assign permissions
        $user->syncPermissions($permissions);

        $this->command->info('Permissions assigned successfully.');
    }
}
