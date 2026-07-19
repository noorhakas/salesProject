<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Antibiotics',
            'Painkillers',
            'Vitamins & Supplements',
            'Cardiovascular',
            'Dermatology',
            'Respiratory',
            'Digestive Health',
            'Diabetes Care',
            'Cosmetics',
            'Baby Care',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}