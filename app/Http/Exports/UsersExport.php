<?php

namespace App\Exports;

use App\Models\User;
use App\Enums\PositionKey;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Http\Exports\Concerns\ReferenceSheetStyle;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    use ReferenceSheetStyle;
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
            ->with([
                'manager:emp_no,name',
                'branches:id,name',
                'branchDepartments.branch:id,name',
                'branchDepartments.department:id,name',
            ])
            ->filter($this->request)
            ->latest();
    }

    public function headings(): array
    {
        return ['Emp No', 'Name', 'Email', 'Phone', 'Whatsapp', 'Username', 'Manager', 'Status', 'Branch', 'Department'];
    }

    public function map($user): array
    {
        return [
            $user->emp_no,
            $user->name,
            $user->email,
            $user->phone,
            $user->whatsapp,
            $user->user_name,
            optional($user->manager)->emp_no.'-'.optional($user->manager)->name,
            $user->status == 1 ? 'Active' : 'Inactive',
            optional($user->branches->first())->name,
            optional($user->branchDepartments->first())->department?->name,
        ];
    }

     /**
     * Column letters matching headings() — A through I (9 columns).
     */
    protected function columns(): array
    {
        return ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I','J'];
    }

 
}