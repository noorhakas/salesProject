<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            'Sales',
            'Marketing',
            'Pharmacy',
            'Human Resources',
            'Finance',
            'Warehouse & Logistics',
            'Customer Service',
            'IT',
        ];

        foreach ($departments as $name) {
            Department::firstOrCreate(['name' => $name]);
        }
    }
}