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
use App\Models\Specialty;

class UserAccountExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithEvents, WithCustomValueBinder
{
    use Exportable;

    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function collection()
    {
        $customers = Customer::select('customers.*')
            ->join('accounts', 'accounts.id', '=', 'customers.account_id')
            ->orderBy('customers.account_id')
            ->get();

        return $customers->map(function ($q) {
            // Fetch up to 5 products assigned to the customer
            $assignedProducts = Product::whereIn('id', function ($query) use ($q) {
                $query->select('product_id')
                    ->from('user_products')
                    ->where('customer_id', $q->id)
                    ->where('user_id', $this->user->id);
            })->pluck('name')->take(5);

            $assignedCustomer = Customer::whereIn('id', function ($query) use ($q) {
                $query->select('customer_id')
                    ->from('user_customers')
                    ->where('customer_id', $q->id)
                    ->where('user_id', $this->user->id);
            })->exists();

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
                $assignedProducts[0] ?? '',
                $assignedProducts[1] ?? '',
                $assignedProducts[2] ?? '',
                $assignedProducts[3] ?? '',
                $assignedProducts[4] ?? '',
                $assignedCustomer ? $this->user?->name : '',
                '',
            ];
        });
    }

    public function bindValue(Cell $cell, $value)
    {
        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        return [
            "CODE", "Group Name", "Account Name", "Account Type", "Account Class", 
            "Doctor Name", "Specialty", "Area", "Class", "Phone",
            "Product 1", "Product 2", "Product 3", "Product 4", "Product 5",
            "Assign", "Action"
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $spreadsheet = $sheet->getParent();

                // Style headers
                $sheet->getStyle('A1:Q1')->getFont()->setName('Calibri')->setSize(15)->setBold(true);
                $sheet->getRowDimension(1)->setRowHeight(20);
                $sheet->mergeCells('K1:O1');
                $sheet->setCellValue('K1', 'Product Items');
                $sheet->getStyle('K1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Column widths
                foreach ($this->columns() as $col) {
                    $width = in_array($col, ['A']) ? 15 : (in_array($col, ['K','L','M','N','O']) ? 70 : 20);
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // Create ProductsSheet and load product names
                $products = Product::where('status',1)->pluck('name')->take(300)->toArray();
                if (!empty($products)) {
                    $productSheet = $spreadsheet->getSheetByName('ProductsSheet');
                    if (!$productSheet) {
                        $productSheet = new Worksheet($spreadsheet, 'ProductsSheet');
                        $spreadsheet->addSheet($productSheet);
                    }
                    foreach ($products as $index => $product) {
                        $productSheet->setCellValue('A' . ($index + 1), $product);
                    }
                    $spreadsheet->addNamedRange(new NamedRange('ProductList', $productSheet, '$A$1:$A$' . count($products)));
                    $productSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
                }

                // Add dropdowns for Products columns (K-O)
                for ($row = 2; $row <= 3000; $row++) {
                    foreach (range('K', 'O') as $col) {
                        $validation = $sheet->getCell("{$col}{$row}")->getDataValidation();
                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setAllowBlank(true);
                        $validation->setShowDropDown(true);
                        $validation->setFormula1('=ProductList');
                    }
                }

                // Add dropdown for Assign (Column P)
                $users = User::where('position', 3)->pluck('name')->take(20)->toArray();
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

                // Add dropdown for Specialty (Column G)
                $specialties = Specialty::pluck('name')->take(20)->toArray();
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
        return range('A', 'Q');
    }
}
