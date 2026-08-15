<?php

namespace App\Http\Imports\Sheets;

use App\Models\User;
use App\Models\Bricks;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserAssignedAreasImport implements ToCollection, WithHeadingRow
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function collection(\Illuminate\Support\Collection $rows)
    {
        $names = $rows
            ->pluck('area_name') // heading "Area Name" => area_name
            ->filter()
            ->map(fn ($name) => trim($name))
            ->unique();

        if ($names->isEmpty()) {
            return;
        }

        $ids = Bricks::whereIn('name', $names)->pluck('id');

        $this->user->bricks()->sync($ids);
    }
}