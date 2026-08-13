<?php

namespace App\Http\Exports\Sheets;

use App\Models\Bricks;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Http\Exports\Concerns\ReferenceSheetStyle;


class BricksReferenceSheet implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    use ReferenceSheetStyle;
    public function query()
    {
        return Bricks::query()
            ->with('branch:id,name')
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [ 'Name', 'Branch'];
    }

    public function map($brick): array
    {
        return [
            $brick->name,
            optional($brick->branch)->name,
        ];
    }

    public function title(): string
    {
        return 'Bricks';
    }

    protected function columns(): array
    {
        return ['A', 'B'];
    }

   
}