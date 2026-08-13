<?php

namespace App\Http\Exports\Sheets;

use App\Models\Gift;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Http\Exports\Concerns\ReferenceSheetStyle;
use App\Enums\GiftTypeEnum;
use Maatwebsite\Excel\Concerns\WithEvents;

class GiftsReferenceSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithEvents
{
    use ReferenceSheetStyle;
    public function query()
    {
        return Gift::query()->orderBy('name');
    }

    public function headings(): array
    {
        return ['Name', 'Type'];
    }

    public function map($gift): array
    {
        return [
            $gift->name,
            $gift->type == GiftTypeEnum::Gift ? 'Gift' : 'Leave Behind',
        ];
    }

    public function title(): string
    {
        return 'Gifts';
    }

    protected function columns(): array
    {
        return ['A', 'B'];
    }

    
}