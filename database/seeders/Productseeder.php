<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Company;
use App\Models\Category;
use App\Models\Department;
use App\Models\Specialty;
use Illuminate\Database\Seeder;
use Faker\Factory as FakerFactory;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = FakerFactory::create();

        $companyIds    = Company::pluck('id');
        $categoryIds   = Category::pluck('id');
        $departmentIds = Department::pluck('id');

        // ملحوظة: الموديل Specialty مش موجود عندي في الملفات اللي بعتهالي،
        // فلو الاسم مختلف أو الجدول فاضي هتحتاج تعدّل السطر ده.
        $specialtyIds = class_exists(Specialty::class) ? Specialty::pluck('id') : collect();

        if ($companyIds->isEmpty() || $categoryIds->isEmpty()) {
            $this->command->warn('لازم تشغل CompanySeeder و CategorySeeder الأول قبل ProductSeeder.');
            return;
        }

        $productNames = [
            'Panadol Extra',
            'Augmentin 1g',
            'Vitamin C 1000mg',
            'Concor 5mg',
            'Cetaphil Gentle Cleanser',
            'Ventolin Inhaler',
            'Nexium 40mg',
            'Glucophage 500mg',
            'Zyrtec 10mg',
            'Betadine Solution',
            'Brufen 400mg',
            'Cataflam 50mg',
            'Flagyl 500mg',
            'Osteocare Tablets',
            'Strepsils Lozenges',
        ];

        foreach ($productNames as $name) {
            $product = Product::create([
                'name'         => $name,
                'specialty_id' => $specialtyIds->isNotEmpty() ? $specialtyIds->random() : null,
                'image'        => null,
                'description'  => 'وصف تجريبي للمنتج ' . $name,
                'price'        => $faker->randomFloat(2, 10, 500),
                'company_id'   => $companyIds->random(),
                'category_id'  => $categoryIds->random(),
                'status'       => $faker->boolean(85),
            ]);

            if ($departmentIds->isNotEmpty()) {
                $product->departments()->syncWithoutDetaching(
                    $departmentIds->random(min(2, $departmentIds->count()))->toArray()
                );
            }
        }
    }
}