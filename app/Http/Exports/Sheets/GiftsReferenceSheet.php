<?php

namespace App\Http\Exports\Sheets;

use App\Models\Gift;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use App\Enums\GiftTypeEnum;

class GiftsReferenceSheet implements FromQuery, WithHeadings, WithMapping, WithEvents, WithTitle
{
    public function query()
    {
        return Gift::query()->orderBy('name');
    }

    public function headings(): array
    {
        return ['Name', 'Type'];
    }

    public function map($gift): array
    {
        return [
            $gift->name,
            $gift->type == GiftTypeEnum::Gift ? 'Gift' : 'Leave Behind',
        ];
    }

    public function title(): string
    {
        return 'Gifts';
    }

    protected function columns(): array
    {
        return ['A', 'B'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $columns = $this->columns();
                $lastColumn = end($columns); // 'C'
                $highestRow = $sheet->getHighestRow();

              
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

               
                foreach ($columns as $char) {
                    $width = match (true) {
                        $char === 'A' => 25,
                        $char === 'B' => 25,
                        default => 20,
                    };

                    $sheet->getColumnDimension($char)->setWidth($width);
                }

          
                for ($row = 2; $row <= $highestRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(22);

                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);
                }

              
                $sheet->getStyle("A1:{$lastColumn}{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

            
                $lastValidationRow = max($highestRow, 500);

                for ($row = 2; $row <= $lastValidationRow; $row++) {
                    $validation = $sheet->getCell("B{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(false);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Invalid Type');
                    $validation->setError('Please select either "Gift" or "Leave Behind" from the list.');
                    $validation->setPromptTitle('Select Type');
                    $validation->setPrompt('Choose the gift type from the dropdown.');
                    $validation->setFormula1('"Gift,Leave Behind"');
                }
            },
        ];
    }
}