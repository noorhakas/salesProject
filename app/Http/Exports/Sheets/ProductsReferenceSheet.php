<?php

namespace App\Http\Exports\Sheets;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Http\Exports\Concerns\ReferenceSheetStyle;

class ProductsReferenceSheet implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithTitle
{
    use ReferenceSheetStyle;
    public function query()
    {
        return Product::query()
            ->with(['category', 'company'])
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Name',
            'Description',
            'Price',
            'Company',
            'Category',
            'Status',
        ];
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->description,
            $product->price,
            $product->company?->name,
            $product->category?->name,
            $product->status,
        ];
    }

    public function title(): string
    {
        return 'Products';
    }

    protected function columns(): array
    {
        return ['A', 'B', 'C', 'D', 'E', 'F'];
    }

  
}