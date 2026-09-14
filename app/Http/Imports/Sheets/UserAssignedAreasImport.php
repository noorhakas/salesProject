<?php

namespace App\Http\Imports\Sheets;

use App\Models\User;
use App\Models\Bricks;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserAssignedAreasImport implements ToCollection, WithHeadingRow
{
    protected User $user;

    public Collection $exist_brick;
    public Collection $dontexist_brick;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->exist_brick = collect();
        $this->dontexist_brick = collect();
    }

    public function collection(Collection $rows)
    {
        $names = $rows
            ->pluck('area_name') // heading "Area Name" => area_name
            ->filter()
            ->map(fn ($name) => trim($name))
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return;
        }

        $bricks = Bricks::whereIn('name', $names)->get(['id', 'name']);

        foreach ($bricks as $brick) {
            $this->exist_brick->add([
                'id'         => $brick->id,
                'brick_name' => $brick->name,
            ]);
        }

        $foundNames = $bricks->pluck('name')->all();

        foreach ($names as $name) {
            if (!in_array($name, $foundNames, true)) {
                $this->dontexist_brick->add([
                    'brick_name' => $name,
                ]);
            }
        }

        $this->user->bricks()->sync($bricks->pluck('id'));
    }
}