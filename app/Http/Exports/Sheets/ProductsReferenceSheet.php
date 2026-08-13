<?php

namespace App\Http\Exports\Sheets;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProductsReferenceSheet implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithTitle
{
    public function query()
    {
        return Product::query()
            ->with(['category', 'company'])
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Name',
            'Description',
            'Price',
            'Company',
            'Category',
            'Status',
        ];
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->description,
            $product->price,
            $product->company?->name,
            $product->category?->name,
            $product->status,
        ];
    }

    public function title(): string
    {
        return 'Products';
    }

    protected function columns(): array
    {
        return ['A', 'B', 'C', 'D', 'E', 'F'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $columns = $this->columns();
                $lastColumn = end($columns);
                $highestRow = $sheet->getHighestRow();

                /*
                 * Header
                 */
                $sheet->getStyle("A1:{$lastColumn}1")
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

                $sheet->getRowDimension(1)->setRowHeight(22);

                /*
                 * Column widths
                 */
                foreach ($columns as $char) {

                    $width = match ($char) {
                        'A' => 30, // Name
                        'B' => 40, // Description
                        'C' => 15, // Price
                        'D' => 25, // Company
                        'E' => 25, // Category
                        'F' => 15, // Status
                        default => 20,
                    };

                    $sheet->getColumnDimension($char)->setWidth($width);
                }

                /*
                 * Rows
                 */
                for ($row = 2; $row <= $highestRow; $row++) {

                    $sheet->getRowDimension($row)->setRowHeight(22);

                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(
                            Border::BORDER_THIN
                        );
                }

                /*
                 * Alignment
                 */
                $sheet->getStyle("A1:{$lastColumn}{$highestRow}")
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