<?php

namespace App\Http\Exports\Concerns;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

trait ReferenceSheetStyle
{
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $columns = $this->columns();

                $lastColumn = end($columns);

                $highestRow = $sheet->getHighestRow();

                /*
                |--------------------------------------------------------------------------
                | Header
                |--------------------------------------------------------------------------
                */

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

                /*
                |--------------------------------------------------------------------------
                | Column Widths
                |--------------------------------------------------------------------------
                */

                foreach ($columns as $char) {

                    $width = $this->columnWidth($char);

                    $sheet
                        ->getColumnDimension($char)
                        ->setWidth($width);
                }

                /*
                |--------------------------------------------------------------------------
                | Rows
                |--------------------------------------------------------------------------
                */

                for ($row = 2; $row <= $highestRow; $row++) {

                    $sheet
                        ->getRowDimension($row)
                        ->setRowHeight(22);

                    $sheet
                        ->getStyle("A{$row}:{$lastColumn}{$row}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(
                            Border::BORDER_THIN
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Alignment
                |--------------------------------------------------------------------------
                */

                $sheet
                    ->getStyle("A1:{$lastColumn}{$highestRow}")
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

    /**
     * Default column width.
     */
    protected function columnWidth(string $column): int
    {
        return match ($column) {
            'A' => 25,
            'F', 'G', 'H' => 30,
            default => 20,
        };
    }
}