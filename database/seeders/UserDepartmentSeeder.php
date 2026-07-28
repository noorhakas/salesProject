<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('user_departments')->insert([
            [
                'user_id' => 2,
                'department_id' => 1,
            ],
             [
                'user_id' => 2,
                'department_id' => 2,
            ],
             [
                'user_id' => 2,
                'department_id' => 3,
            ],
             [
                'user_id' => 2,
                'department_id' => 4,
            ],
             [
                'user_id' => 2,
                'department_id' => 5,
            ],
            [
                'user_id' => 3,
                'department_id' => 3,
            ],
            [
                'user_id' => 3,
                'department_id' => 4,
            ],
             [
                'user_id' => 3,
                'department_id' => 5,
            ],
            [
                'user_id' => 4,
                'department_id' => 4,
            ],
            [
                'user_id' => 5,
                'department_id' =>5,
            ],
        ]);
    }
}