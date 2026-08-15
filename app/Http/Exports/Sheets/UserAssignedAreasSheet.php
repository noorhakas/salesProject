<?php

namespace App\Http\Exports\Sheets;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Http\Exports\Concerns\ReferenceSheetStyle;

class UserAssignedAreasSheet implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    use ReferenceSheetStyle;
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function collection()
    {
        return $this->user->bricks()
            ->select('bricks.id', 'bricks.name')
            ->get()
            ->map(fn ($brick) => [
                'name' => $brick->name,
            ]);
    }

    public function headings(): array
    {
        return [ 'Area Name'];
    }

    public function title(): string
    {
        return 'Areas';
    }

      protected function columns(): array
    {
        return ['A'];
    }

 
}