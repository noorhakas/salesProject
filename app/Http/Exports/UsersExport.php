<?php

namespace App\Exports;

use App\Models\User;
use App\Enums\PositionKey;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Http\Request;

class UsersExport implements FromQuery, WithHeadings, WithMapping
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        return User::query()
            ->where('is_admin', 0)
            ->whereHas('userposition', fn ($q) => $q->where('ps_key', PositionKey::SALES_REP->value))
            ->with(['branches:id,name', 'branchDepartments.branch:id,name', 'branchDepartments.department:id,name'])
            ->filter($this->request)
            ->latest();
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Email', 'Phone', 'Whatsapp', 'Username', 'Status', 'Branches', 'Departments', 'Access All Data'];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone,
            $user->whatsapp,
            $user->user_name,
            $user->status,
            $user->branches->pluck('name')->implode(', '),
            $user->branchDepartments->pluck('department.name')->filter()->implode(', '),
            $user->access_all_data ? 'Yes' : 'No',
        ];
    }
}