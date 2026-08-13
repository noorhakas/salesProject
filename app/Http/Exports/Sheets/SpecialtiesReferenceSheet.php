<?php

namespace App\Http\Exports\Sheets;

use App\Models\Specialty;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithTitle;

class SpecialtiesReferenceSheet implements FromQuery, WithHeadings, WithMapping, WithEvents, WithTitle
{
    public function query()
    {
        return Specialty::query()->orderBy('name');
    }

    public function headings(): array
    {
        return ['Name'];
    }

    public function map($specialty): array
    {
        return [
            $specialty->name,
        ];
    }

    public function title(): string
    {
        return 'Specialties';
    }

    protected function columns(): array
    {
        return ['A'];
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
                    $char === 'A' => 25,
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