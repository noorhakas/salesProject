<?php

namespace App\Http\Exports\Sheets;

use App\Models\AccType;
use App\Http\Exports\Concerns\ReferenceSheetStyle;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;

class AccTypeReferenceSheet implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithTitle
{
    use ReferenceSheetStyle;

    public function query()
    {
        return AccType::query()->orderBy('name');
    }

    public function headings(): array
    {
        return ['Name'];
    }

    public function map($acctype): array
    {
        return [
            $acctype->name,
        ];
    }

    public function title(): string
    {
        return 'Acc-Type';
    }

    protected function columns(): array
    {
        return ['A'];
    }
}