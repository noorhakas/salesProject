<?php

namespace App\Http\Exports;

use App\Models\User;
use App\Enums\PositionKey;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Http\Exports\Concerns\ReferenceSheetStyle;

class SalesRepsExport implements FromQuery, WithHeadings, WithMapping, WithEvents
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
            ->whereHas('userposition', function ($q) {
                $q->where('ps_key', PositionKey::SALES_REP->value);
            })
            ->with([
                'branches:id,name',
                'branchDepartments.branch:id,name',
                'branchDepartments.department:id,name',
                'manager:emp_no,name',
                'position:id,name',
            ])
            ->filter($this->request)
            ->latest();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Whatsapp',
            'Username',
            'Manager',
            'Status',
            'Branches',
            'Departments',
        ];
    }

    public function map($salesRep): array
    {
        return [
            $salesRep->emp_no,
            $salesRep->name,
            $salesRep->email,
            $salesRep->phone,
            $salesRep->whatsapp,
            $salesRep->user_name,

            $salesRep->manager
                ? $salesRep->manager->emp_no . ' - ' . $salesRep->manager->name
                : '',

            $salesRep->status == 1 ? 'Active' : 'Inactive',

            $salesRep->branches
                ->pluck('name')
                ->filter()
                ->unique()
                ->implode(', '),

            $salesRep->branchDepartments
                ->map(fn ($item) => $item->department?->name)
                ->filter()
                ->unique()
                ->implode(', '),
        ];
    }

    protected function columns(): array
    {
        return [
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
        ];
    }
}