<?php

namespace App\Http\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Http\Request;

class ManagersExport implements FromQuery, WithHeadings, WithMapping
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        return User::query()
            ->whereHas('userposition', fn ($q) => $q->where('ps_key', \App\Enums\PositionKey::SUPERVISOR->value))
            ->with(['branches:id,name', 'branchDepartments.branch:id,name', 'branchDepartments.department:id,name'])
            ->filter($this->request)
            ->latest();
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Email', 'Phone', 'Whatsapp', 'Username', 'Status', 'Branches', 'Departments'];
    }

    public function map($manager): array
    {
        return [
            $manager->id,
            $manager->name,
            $manager->email,
            $manager->phone,
            $manager->whatsapp,
            $manager->user_name,
            $manager->status,
            $manager->branches->pluck('name')->implode(', '),
            $manager->branchDepartments->pluck('department.name')->filter()->implode(', '),
        ];
    }
}