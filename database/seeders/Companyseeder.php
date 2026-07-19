<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            'Pfizer',
            'Novartis',
            'Bayer',
            'Sanofi',
            'GlaxoSmithKline',
            'Merck',
            'AstraZeneca',
            'Roche',
            'Johnson & Johnson',
            'Eli Lilly',
        ];

        foreach ($companies as $name) {
            Company::firstOrCreate(['name' => $name]);
        }
    }
}