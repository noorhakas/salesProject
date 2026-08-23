<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class AssignPermissionsToUserSeeder extends Seeder
{
    public function run()
    {
        $user = User::find(93);

        if (!$user) {
            $this->command->error('User not found!');
            return;
        }

        // Get all current permissions
        $permissions = Permission::all();

        // Remove old permissions and assign the new ones
        $user->syncPermissions($permissions);

        $this->command->info(
            'Old permissions removed and all current permissions assigned successfully.'
        );
    }
}