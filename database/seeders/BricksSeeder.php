<?php

namespace Database\Seeders;

use App\Models\Bricks;
use Illuminate\Database\Seeder;

class BricksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bricks = [
            'Cairo - East',
            'Cairo - West',
            'Giza',
            'Alexandria',
            'Dakahlia',
            'Sharqia',
            'Qalyubia',
        ];

        foreach ($bricks as $name) {
            Bricks::firstOrCreate(['name' => $name]);
        }
    }
}