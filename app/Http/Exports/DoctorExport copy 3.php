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

                // Apply header styles
                $sheet->getStyle('A1:O1')->getFont()->setName('Calibri')->setSize(15)->setBold(true);
                $sheet->getRowDimension(1)->setRowHeight(17);
                $sheet->getStyle('O1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);

                // Set column widths
                foreach ($this->columns() as $char) {
                    $width = in_array($char, ['A']) ? 15 : (in_array($char, [/*'B', 'D', */'M','K','L','N','O','P']) ? 40 : 20);
                    $sheet->getColumnDimension($char)->setWidth($width);
                }

                // Ensure "ProductsSheet" exists
                $productSheet = $spreadsheet->getSheetByName('ProductsSheet') ?? new Worksheet($spreadsheet, 'ProductsSheet');
                if (!$spreadsheet->sheetNameExists('ProductsSheet')) {
                    $spreadsheet->addSheet($productSheet);
                }

                // Fetch users
                $users = User::where('position', 3)->pluck('name')->take(30)->toArray();
                if (!empty($users)) {
                    $formattedUsers = '"' . implode(',', $users) . '"';
                    for ($row = 2; $row <= 3000; $row++) {
                        $validation = $sheet->getCell("P{$row}")->getDataValidation();
                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setAllowBlank(true);
                        $validation->setShowDropDown(true);
                        $validation->setFormula1($formattedUsers);
                    }
                }

                // Fetch products & store in "ProductsSheet"
           /*     $products = Product::where('status',1)->pluck('name')->where('status',1)->take(300)->toArray();
                if (!empty($products)) {
                    foreach ($products as $index => $product) {
                        $productSheet->setCellValue("A" . ($index + 1), $product);
                    }
                    $spreadsheet->addNamedRange(new NamedRange('ProductList', $productSheet, '$A$1:$A$' . count($products)));
                }

                // Apply dropdown to Product column (M)
                for ($row = 2; $row <= 3000; $row++) {
                    $validation = $sheet->getCell("L{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('=ProductList');
                    $validation->setShowInputMessage(true);
                    $validation->setPromptTitle('Multi-Select Instructions');
                    $validation->setPrompt('Select a product from the list or manually type multiple products separated by commas.');
                }*/

                $products = Product::where('status', 1)->pluck('name')->take(300)->toArray();

                if (!empty($products)) {
                    // Create ProductsSheet and list products there
                    $productSheet = $spreadsheet->getSheetByName('ProductsSheet');
                    if (!$productSheet) {
                        $productSheet = new Worksheet($spreadsheet, 'ProductsSheet');
                        $spreadsheet->addSheet($productSheet);
                    }
                    foreach ($products as $index => $product) {
                        $productSheet->setCellValue("A" . ($index + 1), $product);
                    }
                    $spreadsheet->addNamedRange(new NamedRange('ProductList', $productSheet, '$A$1:$A$' . count($products)));
                    $productSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
                }

                // Merge cells for "Product Items" (K1:O1)
                $sheet->mergeCells('K1:O1');
                $sheet->setCellValue('K1', 'Product Items');
                $sheet->getStyle('K1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Set dropdowns for each Product Item column
                for ($row = 2; $row <= 3000; $row++) {
                    foreach (range('K', 'O') as $col) { // K, L, M, N, O
                        $validation = $sheet->getCell("{$col}{$row}")->getDataValidation();
                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setAllowBlank(true);
                        $validation->setShowDropDown(true);
                        $validation->setFormula1('=ProductList');
                    }
                }

                // Fetch & apply Specialty dropdown (Column G)
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

                // Hide "ProductsSheet"
                $productSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
                $spreadsheet->setActiveSheetIndex(0);
            },
        ];
    }

    public function columns()
    {
        return range('A', 'N');
    }
}
