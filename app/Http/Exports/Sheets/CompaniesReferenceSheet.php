<?php

namespace App\Http\Exports\Sheets;

use App\Models\Company;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CompaniesReferenceSheet implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithTitle
{
    public function query()
    {
        return Company::query()
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Name',
        ];
    }

    public function map($company): array
    {
        return [
            $company->name,
        ];
    }

    public function title(): string
    {
        return 'Companies';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:A1")
                    ->applyFromArray([
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

                $sheet->getColumnDimension('A')->setWidth(30);

                $sheet->getRowDimension(1)->setRowHeight(22);

                for ($row = 2; $row <= $highestRow; $row++) {

                    $sheet->getRowDimension($row)->setRowHeight(22);

                    $sheet->getStyle("A{$row}:A{$row}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(
                            Border::BORDER_THIN
                        );
                }

                $sheet->getStyle("A1:A{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    )
                    ->setVertical(
                        Alignment::VERTICAL_CENTER
                    );
            },
        ];
    }
}