<?php

namespace Database\Seeders;

use App\Models\Classes;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specialties = [
            'A',
            'B',
            'C',
        ];

        foreach ($specialties as $name) {
            Classes::firstOrCreate(['name' => $name]);
        }
    }
}