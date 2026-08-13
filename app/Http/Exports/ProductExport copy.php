<?php

namespace App\Http\Exports;

use App\Http\Resources\DoctorXlsxResource;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Product;

class ProductExport implements FromCollection, WithHeadings,WithEvents
{
    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */

    public function collection()
    {
          $products = Product::has('category')->get();
           $data = $products->transform(function ($q){
             return[
                 'uuid'=>$q->Uuid,
                 'name'=>$q->name,
                 'company'=>optional($q->comapny)->name,
                 'category'=>optional($q->category)->name,
				 'Price'=>$q->price,
				 'description'=>$q->description,
                 'active' => $q->status == 1 ? 'Yes' : 'No',

             ];
          });
        return $data;
    }

    public function headings() :array
    {
        return ["UUID","Product Name", "Company","Therapeutic Category","WSP KD", "Description","Active"];
    }

	 public function registerEvents(): array
    {
        $styleArray = ['font' => ['bold' => true]];

        return [


            
            AfterSheet::class => function(AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Add dropdown validation to column G (Active = 7th column)
                for ($row = 2; $row <= $highestRow; $row++) {
                    $validation = $sheet->getCell("G{$row}")->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('"Yes,No"');
                }

                $event->getSheet()->getDelegate()->getStyle('A1:AK1')->getFont()->setName('Calibri')->setSize(15);
                $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(17);
                $event->sheet->getDelegate()->getStyle('A1:AK1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK);
               // $event->sheet->getDelegate()->getStyle('A1:AK1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                // ->getStartColor()->set('FFFFF');
                foreach ($this->coloumns() as $charachter) {
                    $width_value = in_array($charachter,['A'] ) ? 12 : (in_array($charachter,['B','D','F'] ) ? 50 : 20);
                    $event->sheet->getDelegate()->getColumnDimension($charachter)->setWidth($width_value);
                }
            },
            ];
    }



	public function coloumns()
    {
        return ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
    }
}
