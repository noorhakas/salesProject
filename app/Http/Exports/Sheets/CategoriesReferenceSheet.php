<?php

namespace App\Http\Exports\Sheets;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Http\Exports\Concerns\ReferenceSheetStyle;
use Maatwebsite\Excel\Concerns\WithEvents;


class CategoriesReferenceSheet implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithEvents
{
    use ReferenceSheetStyle;

    public function query()
    {
        return Category::query()
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Name',
        ];
    }

    public function map($category): array
    {
        return [
            $category->name,
        ];
    }

    public function title(): string
    {
        return 'Categories';
    }

}