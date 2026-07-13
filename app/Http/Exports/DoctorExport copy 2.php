<?php

namespace App\Http\Exports;

use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Customer;
use App\Models\User;
use App\Models\Product;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Models\Specialty;

class DoctorExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithEvents, WithCustomValueBinder
{
    use Exportable;

    public function collection()
    {
        $customers = Customer::select('customers.*')
            ->join('accounts', 'accounts.id', '=', 'customers.account_id')
            ->orderBy('customers.account_id')
            ->get();

        return $customers->map(function ($q) {
            return [
                'code' => $q->Uuid,
                'group_name' => optional($q->pharmacyGroup)->name,
                'account_name' => optional($q->account)->name,
                'account_type' => optional($q->account?->accType)->name,
                'account_class' => optional($q->account?->class)->name,
                'doctor_name' => $q->name,
                'specialty' => optional($q->specialty)->name ?? '',
                'area' => optional($q->account?->brick)->name,
                'class' => optional($q->class)->name ?? '',
                'phone' => $q->phone,
                 "", "", "", "", "", // ??? ????? ????? ????????
                'user' => '',
                'action' => ["del_account", "del_doctor"],
            ];
        });
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_array($value)) {
            $validation = $cell->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('"' . collect($value)->join(',') . '"');
            $value = '';
        }
        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        return ["CODE", "Group Name", "Account Name", "Account Type", "Account Class", "Doctor Name", "Specialty", "Area", "Class", "Phone",
                 "", "", "", "", "", // ??? ????? ????? ???????
                  "Assign", "Action"];
    }

   public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {

            $sheet = $event->sheet->getDelegate();
            $spreadsheet = $sheet->getParent();

            $highestColumn = $sheet->getHighestColumn();
            $highestRow = $sheet->getHighestRow();

            // =========================
            // TITLE ROW (Row 1)
            // =========================
            $sheet->insertNewRowBefore(1, 1);
            $sheet->mergeCells("A1:{$highestColumn}1");
            $sheet->setCellValue("A1", "Doctors Export");

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
                    'startColor' => ['rgb' => '1F4E78'],
                ],
            ]);

            // =========================
            // HEADER ROW STYLE (Row 2)
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
            // PRODUCT GROUP HEADER (K1:O1)
            // =========================
            $sheet->mergeCells('K1:O1');
            $sheet->setCellValue('K1', 'Product Items');

            $sheet->getStyle('K1')->applyFromArray([
                'font' => [
                    'bold' => true,
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
            // ROW HEIGHTS + BORDERS
            // =========================
            for ($row = 2; $row <= $highestRow + 1; $row++) {

                $sheet->getRowDimension($row)->setRowHeight(22);

                $sheet->getStyle("A{$row}:{$highestColumn}{$row}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            }

            // =========================
            // COLUMN WIDTHS
            // =========================
            foreach ($this->columns() as $char) {

                if ($char > $highestColumn) break;

                $width = match (true) {
                    $char === 'A' => 15,
                    in_array($char, ['F', 'G']) => 25,
                    in_array($char, ['K','L','M','N','O']) => 35,
                    default => 20,
                };

                $sheet->getColumnDimension($char)->setWidth($width);
            }

            // =========================
            // GLOBAL ALIGNMENT
            // =========================
            $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // =========================
            // FREEZE HEADER
            // =========================
            $sheet->freezePane('A3');

            // =========================
            // PRODUCTS SHEET
            // =========================
            $productSheet = $spreadsheet->getSheetByName('ProductsSheet')
                ?? new Worksheet($spreadsheet, 'ProductsSheet');

            if (!$spreadsheet->sheetNameExists('ProductsSheet')) {
                $spreadsheet->addSheet($productSheet);
            }

            $products = Product::where('status', 1)
                ->pluck('name')
                ->take(300)
                ->toArray();

            if (!empty($products)) {
                foreach ($products as $i => $product) {
                    $productSheet->setCellValue("A" . ($i + 1), $product);
                }

                $spreadsheet->addNamedRange(
                    new NamedRange('ProductList', $productSheet, '$A$1:$A$' . count($products))
                );

                $productSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
            }

            // =========================
            // PRODUCT DROPDOWNS (K-O)
            // =========================
            for ($r = 3; $r <= 3000; $r++) {
                foreach (range('K', 'O') as $col) {

                    $validation = $sheet->getCell("{$col}{$r}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('=ProductList');
                }
            }

            // =========================
            // SPECIALTY DROPDOWN (G)
            // =========================
            $specialties = Specialty::pluck('name')->take(30)->toArray();

            if (!empty($specialties)) {

                $list = '"' . implode(',', $specialties) . '"';

                for ($r = 3; $r <= 3000; $r++) {

                    $validation = $sheet->getCell("G{$r}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($list);
                }
            }

            $spreadsheet->setActiveSheetIndex(0);
        },
    ];
}

    public function columns()
    {
        return range('A', 'Q');
    }
}
