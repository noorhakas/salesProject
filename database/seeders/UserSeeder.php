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

    }
}