<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Visit;
use App\Models\Product;
use App\Models\Gift;
use App\Models\VisitDetails;

class VisitDetailsSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::pluck('id')->toArray();
        $gifts = Gift::pluck('id')->toArray();

        Visit::all()->each(function ($visit) use ($products, $gifts) {

            $count = rand(2, 6);

            for ($i = 0; $i < $count; $i++) {

                $isGift = rand(0, 1);

                VisitDetails::create([
                    'visit_id' => $visit->id,
                    'item_type' => $isGift,
                    'item_id' => $isGift
                        ? $gifts[array_rand($gifts)]
                        : $products[array_rand($products)],
                    'count_of_sample' => rand(1, 10),
                ]);
            }
        });
    }
}