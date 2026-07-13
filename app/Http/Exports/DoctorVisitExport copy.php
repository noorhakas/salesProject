<?php

namespace App\Http\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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
                $count = count($doctor['visit_dates']);
                if ($count > $maxDates) {
                    $maxDates = $count;
                }
            }
        }

        $headings = ['Account', 'Doctor', 'Visits'];

        for ($i = 1; $i <= $maxDates; $i++) {
            $headings[] = "Date $i";
        }

        return $headings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $columnCount = count($this->headings());
                $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

                // 1. Add the title in A1 and merge cells
                $event->sheet->mergeCells("A1:{$lastColumn}1");
                $event->sheet->setCellValue("A1", $this->title);
                $event->sheet->getStyle("A1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'name' => 'Calibri',
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // 2. Style headings in row 2
                $event->sheet->getDelegate()->getStyle("A2:{$lastColumn}2")
                    ->getFont()->setName('Calibri')->setSize(15)->getColor()->setARGB(Color::COLOR_BLACK);
                $event->sheet->getDelegate()->getRowDimension(2)->setRowHeight(17);

                // 3. Set column widths
                foreach ($this->columns() as $char) {
                    if ($char > $lastColumn) break;
                    $width = in_array($char, ['C']) ? 15 : 30;
                    $event->sheet->getDelegate()->getColumnDimension($char)->setWidth($width);
                }
            },
        ];
    }

    public function columns()
    {
        // Generate A-Z and AA-ZZ for wide sheets
        $columns = [];
        foreach (range('A', 'Z') as $letter) {
            $columns[] = $letter;
        }
        foreach (range('A', 'Z') as $prefix) {
            foreach (range('A', 'Z') as $suffix) {
                $columns[] = $prefix . $suffix;
            }
        }
        return $columns;
    }
}
