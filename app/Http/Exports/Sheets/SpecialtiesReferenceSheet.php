<?php

namespace App\Http\Exports\Sheets;

use App\Models\Specialty;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Http\Exports\Concerns\ReferenceSheetStyle;
use Maatwebsite\Excel\Concerns\WithTitle;

class SpecialtiesReferenceSheet implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    use ReferenceSheetStyle;
    public function query()
    {
        return Specialty::query()->orderBy('name');
    }

    public function headings(): array
    {
        return ['Name'];
    }

    public function map($specialty): array
    {
        return [
            $specialty->name,
        ];
    }

    public function title(): string
    {
        return 'Specialties';
    }

    protected function columns(): array
    {
        return ['A'];
    }


}