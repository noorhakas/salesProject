<?php

namespace App\Http\Imports\Sheets;

use App\Models\Bricks;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserAssignedAreasImport implements ToCollection, WithHeadingRow
{
    public Collection $exist_brick;
    public Collection $dontexist_brick;

    public function __construct()
    {
        $this->exist_brick = collect();
        $this->dontexist_brick = collect();
    }

    public function collection(Collection $rows)
    {
        $names = $rows
            ->pluck('area_name')
            ->filter()
            ->map(fn ($name) => trim($name))
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return;
        }

        $bricks = Bricks::whereIn('name', $names)
            ->get(['id', 'name']);

        // Areas that exist
        foreach ($bricks as $brick) {
            $this->exist_brick->add([
                'id' => $brick->id,
                'brick_name' => $brick->name,
            ]);
        }

        // Areas that don't exist
        $foundNames = $bricks
            ->pluck('name')
            ->all();

        foreach ($names as $name) {
            if (!in_array($name, $foundNames, true)) {
                $this->dontexist_brick->add([
                    'brick_name' => $name,
                ]);
            }
        }
    }
}
