<?php

namespace App\Http\Imports\Sheets;

use App\Models\Product;
use App\Models\Category;
use App\Models\Company;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class ProductsImportSheet implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure
{
    use SkipsFailures;

    public function model(array $row)
    {
        $name = trim($row['name'] ?? '');

        if ($name === '') {
            return null;
        }

        /*
         * Resolve Category by name
         */
        $categoryId = null;

        if (!empty($row['category'])) {
            $categoryId = Category::query()
                ->where('name', trim($row['category']))
                ->value('id');
        }

        /*
         * Resolve Company by name
         */
        $companyId = null;

        if (!empty($row['company'])) {
            $companyId = Company::query()
                ->where('name', trim($row['company']))
                ->value('id');
        }

        /*
         * Create or update product
         */
        return Product::updateOrCreate(
            [
                'name' => $name,
            ],
            [
                'name'        => $name,
                'description' => $row['description'] ?? null,
                'price'       => $row['price'] ?? null,
                'company_id'  => $companyId,
                'category_id' => $categoryId,
                'status'      => $row['status'] ?? null,
            ]
        );
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'company' => [
                'nullable',
                'string',
                'max:255',
            ],

            'category' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'nullable',
            ],
        ];
    }
}