<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Position;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
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
        | Branches (هات الفروع الموجودة أو اعمل واحد افتراضي لو مفيش)
        |--------------------------------------------------------------------------
        */

        $cairoBranch      = Branch::firstOrCreate(['name' => 'Cairo - Nasr City'], ['address' => 'Nasr City, Cairo', 'phone' => '0221234567']);
        $alexBranch       = Branch::firstOrCreate(['name' => 'Alexandria - Smouha'], ['address' => 'Smouha, Alexandria', 'phone' => '0345678912']);
        $mansouraBranch   = Branch::firstOrCreate(['name' => 'Mansoura - Downtown'], ['address' => 'Downtown, Mansoura', 'phone' => '0501234567']);

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        $password = '123456';

        // Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'System Admin',
                'user_name' => 'admin',
                'password' => $password,
                'phone' => '01011111111',
                'whatsapp' => '01011111111',
                'status' => 1,
                'position' => $areaManager->id,
                'access_all_data' => 1,
                'manager_id' => null,
            ]
        );
        // الأدمن له صلاحية على كل الفروع
        $admin->branches()->sync([$cairoBranch->id, $alexBranch->id, $mansouraBranch->id]);


        // Area Manager
        $manager = User::updateOrCreate(
            ['email' => 'manager@gmail.com'],
            [
                'name' => 'Ahmed Manager',
                'user_name' => 'manager',
                'password' => $password,
                'phone' => '01022222222',
                'whatsapp' => '01022222222',
                'status' => 1,
                'position' => $areaManager->id,
                'access_all_data' => 1,
                'manager_id' => $admin->id,
            ]
        );
        $manager->branches()->sync([$cairoBranch->id, $alexBranch->id]);


        // Supervisor
        $supervisorUser = User::updateOrCreate(
            ['email' => 'supervisor@gmail.com'],
            [
                'name' => 'Mohamed Supervisor',
                'user_name' => 'supervisor',
                'password' => $password,
                'phone' => '01033333333',
                'whatsapp' => '01033333333',
                'status' => 1,
                'position' => $supervisor->id,
                'access_all_data' => 0,
                'manager_id' => $manager->id,
            ]
        );
        $supervisorUser->branches()->sync([$cairoBranch->id]);


        // Sales 1
        $sales1 = User::updateOrCreate(
            ['email' => 'sales1@gmail.com'],
            [
                'name' => 'Omar Sales',
                'user_name' => 'sales1',
                'password' => $password,
                'phone' => '01044444444',
                'whatsapp' => '01044444444',
                'status' => 1,
                'position' => $salesRep->id,
                'access_all_data' => 0,
                'manager_id' => $supervisorUser->id,
            ]
        );
        $sales1->branches()->sync([$cairoBranch->id]);


        // Sales 2
        $sales2 = User::updateOrCreate(
            ['email' => 'sales2@gmail.com'],
            [
                'name' => 'Ali Sales',
                'user_name' => 'sales2',
                'password' => $password,
                'phone' => '01055555555',
                'whatsapp' => '01055555555',
                'status' => 1,
                'position' => $salesRep->id,
                'access_all_data' => 0,
                'manager_id' => $supervisorUser->id,
            ]
        );
        $sales2->branches()->sync([$mansouraBranch->id]);
    }
}