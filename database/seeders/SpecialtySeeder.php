<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Seeder;

class SpecialtySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specialties = [
            'General Practice',
            'Cardiology',
            'Dermatology',
            'Pediatrics',
            'Gynecology',
            'Internal Medicine',
            'Orthopedics',
            'ENT',
            'Ophthalmology',
            'Dentistry',
        ];

        foreach ($specialties as $name) {
            Specialty::firstOrCreate(['name' => $name]);
        }
    }
}