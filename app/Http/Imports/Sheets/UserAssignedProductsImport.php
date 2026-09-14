<?php

namespace App\Http\Imports\Sheets;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserAssignedProductsImport implements ToCollection, WithHeadingRow
{
    public Collection $exist_product;
    public Collection $dontexist_product;

    public function __construct()
    {
        $this->exist_product = collect();
        $this->dontexist_product = collect();
    }

    public function collection(Collection $rows)
    {
        $names = $rows
            ->pluck('product_name')
            ->filter()
            ->map(fn ($name) => trim($name))
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return;
        }

        $products = Product::whereIn('name', $names)
            ->get(['id', 'name']);

        // Products that exist
        foreach ($products as $product) {
            $this->exist_product->add([
                'id' => $product->id,
                'product_name' => $product->name,
            ]);
        }

        // Products that don't exist
        $foundNames = $products
            ->pluck('name')
            ->all();

        foreach ($names as $name) {
            if (!in_array($name, $foundNames, true)) {
                $this->dontexist_product->add([
                    'product_name' => $name,
                ]);
            }
        }
    }
}
