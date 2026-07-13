<?php

namespace App\Http\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DoctorVisitExport implements FromCollection, WithHeadings, WithEvents
{
    protected $data;
    protected $title;

    public function __construct($visits, $title = '')
    {
        $this->data = $visits;
        $this->title = $title;
    }

    public function collection()
    {
        $flatData = collect();

        foreach ($this->data as $visitGroup) {
            foreach ($visitGroup['doctors'] as $doctor) {

                $row = [
                    'account'      => $visitGroup['account_name'],
                    'doctor'       => $doctor['doctor_name'],
                    'specialty'       => $doctor['specialty_name'],
                    'class'       => $doctor['class_name'],
                    'visits_count' => $doctor['visits_count'],
                ];

                foreach ($doctor['visit_dates'] as $index => $date) {
                    $row['visit_date_' . ($index + 1)] = $date;
                }

                $flatData->push($row);
            }
        }

        return $flatData;
    }

    public function headings(): array
    {
        $maxDates = 0;

        foreach ($this->data as $visitGroup) {
            foreach ($visitGroup['doctors'] as $doctor) {
                $maxDates = max($maxDates, count($doctor['visit_dates']));
            }
        }

        $headings = ['Account', 'Doctor','Specialty','Class', 'Visits'];

        for ($i = 1; $i <= $maxDates; $i++) {
            $headings[] = "Date $i";
        }

        return $headings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                // =========================
                // TITLE ROW
                // =========================
                $sheet->insertNewRowBefore(1, 1);
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->setCellValue("A1", $this->title ?: 'Doctor Visits Report');

                $sheet->getRowDimension(1)->setRowHeight(40);

                $sheet->getStyle("A1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 18,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '5A0A0A'],
                    ],
                ]);

                // =========================
                // HEADER STYLE (Row 2)
                // =========================
                $sheet->getRowDimension(2)->setRowHeight(25);

                $sheet->getStyle("A2:{$highestColumn}2")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '000000'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E7E6E6'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // =========================
                // DATA ROWS
                // =========================
                for ($row = 3; $row <= $highestRow + 1; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(22);

                    $sheet->getStyle("A{$row}:{$highestColumn}{$row}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);
                }

                // =========================
                // COLUMN WIDTHS
                // =========================
                foreach (range('A', $highestColumn) as $col) {
                    $width = match ($col) {
                        'A' => 25,   // Account
                        'B' => 25,   // Doctor
                        'C' => 12,   // Visits count
                        default => 18,
                    };

                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // =========================
                // ALIGNMENT GLOBAL
                // =========================
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}