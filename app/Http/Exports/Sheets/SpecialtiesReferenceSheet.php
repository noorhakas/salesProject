<?php

namespace App\Http\Exports\Sheets;

use App\Models\Specialty;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SpecialtiesReferenceSheet implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function query()
    {
        return Specialty::query()->orderBy('name');
    }

    public function headings(): array
    {
        return ['ID', 'Name'];
    }

    public function map($specialty): array
    {
        return [
            $specialty->id,
            $specialty->name,
        ];
    }

    public function title(): string
    {
        return 'Specialties';
    }
}