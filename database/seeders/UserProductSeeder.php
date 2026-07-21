<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Position;
use App\Models\Product;
use App\Models\UserProducts;

class UserProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salesPosition = Position::where('ps_key', 'sales_rep')->first();

        if (!$salesPosition) {
            $this->command->warn('لازم تشغل UserSeeder الأول عشان يعمل position "sales_rep".');
            return;
        }

        $salesUsers = User::where('position', $salesPosition->id)->get();

        if ($salesUsers->isEmpty()) {
            $this->command->warn('مفيش يوزرز بوزيشن sales_rep. شغل UserSeeder الأول.');
            return;
        }

        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->warn('مفيش Products. شغل ProductSeeder الأول قبل UserProductSeeder.');
            return;
        }

        foreach ($salesUsers as $salesUser) {
            $count = min($products->count(), mt_rand(2, 5));

            $assignedProducts = $products->random($count);

            if (!$assignedProducts instanceof \Illuminate\Support\Collection) {
                $assignedProducts = collect([$assignedProducts]);
            }

            foreach ($assignedProducts as $product) {
                UserProducts::firstOrCreate([
                    'user_id'    => $salesUser->id,
                    'product_id' => $product->id,
                ]);
            }
        }

        $this->command->info('تم ربط الـ Sales Reps بالـ Products بنجاح.');
    }
}