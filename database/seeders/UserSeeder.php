<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Position;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // إنشاء الـ Positions
        $areaManager = Position::create([
            'ps_key' => 'area_manager',
            'name' => 'Area manager',
            'parent_id' => 0,
        ]);

        $supervisor = Position::create([
            'ps_key' => 'supervisor',
            'name' => 'Supervisor',
            'parent_id' => $areaManager->id,
        ]);

        $salesRep = Position::create([
            'ps_key' => 'sales_rep',
            'name' => 'Sales',
            'parent_id' => $supervisor->id,
        ]);


        // إنشاء User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'user_name' => 'admin',
            'password' => Hash::make('123456'),
            'phone' => '01000000000',
            'whatsapp' => '01000000000',
            'status' => 1,
            'position' => $areaManager->id,
        ]);


         User::create([
            'name' => 'Area User',
            'email' => 'area@gmail.com',
            'user_name' => 'Area',
            'password' => Hash::make('123456'),
            'phone' => '01000000000',
            'whatsapp' => '01000000000',
            'status' => 1,
            'position' => $areaManager->id,
        ]);
    }
}