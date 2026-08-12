<?php

namespace App\Exports;

use App\Models\User;
use App\Enums\PositionKey;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithEvents
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

    public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {

            $sheet = $event->sheet->getDelegate();
            $columns = $this->columns();
            $lastColumn = end($columns); // 'I'
            $highestRow = $sheet->getHighestRow();

            // =========================
            // HEADER STYLE
            // =========================
            $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                'font' => [
                    'name'  => 'Calibri',
                    'size'  => 15,
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0f766e'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'FFFFFF'],
                    ],
                ],
            ]);

            $sheet->getRowDimension(1)->setRowHeight(22);

            // =========================
            // COLUMN WIDTHS
            // =========================
            foreach ($columns as $char) {
                $width = match (true) {
                    $char === 'A' => 15,
                    in_array($char, ['F', 'G', 'H']) => 30,
                    default => 20,
                };

                $sheet->getColumnDimension($char)->setWidth($width);
            }

            // =========================
            // ROW HEIGHTS + BORDERS
            // =========================
            for ($row = 2; $row <= $highestRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(22);

                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            }

            // =========================
            // GLOBAL ALIGNMENT
            // =========================
            $sheet->getStyle("A1:{$lastColumn}{$highestRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        },
    ];
}
}