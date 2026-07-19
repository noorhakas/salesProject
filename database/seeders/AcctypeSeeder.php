<?php

namespace Database\Seeders;

use App\Models\AccType;
use Illuminate\Database\Seeder;

class AccTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Pharmacy',   'is_pharmacy' => 1],
            ['name' => 'Clinic',     'is_pharmacy' => 0],
            ['name' => 'Hospital',   'is_pharmacy' => 0],
            ['name' => 'Distributor','is_pharmacy' => 0],
        ];

        foreach ($types as $data) {
            AccType::firstOrCreate(['name' => $data['name']], ['is_pharmacy' => $data['is_pharmacy']]);
        }
    }
}