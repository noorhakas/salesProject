<?php

namespace App\Http\Imports\Sheets;

use App\Models\User;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserAssignedProductsImport implements ToCollection, WithHeadingRow
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function collection(\Illuminate\Support\Collection $rows)
    {
        $names = $rows
            ->pluck('product_name') // heading "Product Name" => product_name
            ->filter()
            ->map(fn ($name) => trim($name))
            ->unique();

        if ($names->isEmpty()) {
            return;
        }

        $ids = Product::whereIn('name', $names)->pluck('id');

        $this->user->products()->sync($ids);
    }
}