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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Models\Product;
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

            // =========================
            // HEADER STYLE (UPDATED)
            // =========================
            $sheet->getStyle('A1:O1')->applyFromArray([
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
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '5A0A0A'], // 👈 نبيتي غامق
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                ],
            ]);

            $sheet->getRowDimension(1)->setRowHeight(22);

            // =========================
            // COLUMN WIDTHS
            // =========================
            foreach ($this->columns() as $char) {

                $width = match (true) {
                    $char === 'A' => 15,
                    in_array($char, ['F', 'G', 'H']) => 30,
                    in_array($char, ['K','L','M','N','O']) => 40,
                    default => 20,
                };

                $sheet->getColumnDimension($char)->setWidth($width);
            }

            // =========================
            // ROW HEIGHTS + BORDERS
            // =========================
            $highestRow = $sheet->getHighestRow();

            for ($row = 2; $row <= $highestRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(22);

                $sheet->getStyle("A{$row}:O{$row}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            }

            // =========================
            // GLOBAL ALIGNMENT
            // =========================
            $sheet->getStyle("A1:O{$highestRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            // =========================
            // PRODUCT GROUP HEADER
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
                    'startColor' => ['rgb' => '800020'], // 👈 نبيتي أغمق للـ group
                ],
            ]);

            // =========================
            // PRODUCTS SHEET (UNCHANGED LOGIC)
            // =========================
            $productSheet = $spreadsheet->getSheetByName('ProductsSheet')
                ?? new Worksheet($spreadsheet, 'ProductsSheet');

            if (!$spreadsheet->sheetNameExists('ProductsSheet')) {
                $spreadsheet->addSheet($productSheet);
            }

            $products = Product::where('status', 1)->pluck('name')->take(300)->toArray();

            if (!empty($products)) {
                foreach ($products as $index => $product) {
                    $productSheet->setCellValue("A" . ($index + 1), $product);
                }

                $spreadsheet->addNamedRange(
                    new NamedRange('ProductList', $productSheet, '$A$1:$A$' . count($products))
                );

                $productSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
            }

            // =========================
            // DROPDOWN PRODUCT COLUMNS
            // =========================
            for ($row = 2; $row <= 3000; $row++) {
                foreach (range('K', 'O') as $col) {
                    $validation = $sheet->getCell("{$col}{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('=ProductList');
                }
            }

            // =========================
            // SPECIALTY DROPDOWN
            // =========================
            $specialties = Specialty::pluck('name')->take(30)->toArray();

            if (!empty($specialties)) {
                $formattedSpecialties = '"' . implode(',', $specialties) . '"';

                for ($row = 2; $row <= 3000; $row++) {
                    $validation = $sheet->getCell("G{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($formattedSpecialties);
                }
            }

            $spreadsheet->setActiveSheetIndex(0);
        },
    ];
}

    public function columns()
    {
        return range('A', 'N');
    }
}
