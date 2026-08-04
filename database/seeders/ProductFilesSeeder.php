<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductFiles;

class ProductFilesSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        foreach ($products as $product) {

            ProductFiles::create([
                'product_id' => $product->id,
                'file' => 'product_files/sample1.pdf',
            ]);

            ProductFiles::create([
                'product_id' => $product->id,
                'file' => 'product_files/sample2.jpg',
            ]);

        }
    }
}