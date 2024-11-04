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
use App\Models\Customer;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;


class DoctorExport extends DefaultValueBinder implements FromCollection, WithHeadings,WithEvents, WithCustomValueBinder
{
    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */

    public function collection()
    {
          $customer = Customer::select('customers.*')->join('accounts','accounts.id','=','customers.account_id')
                        ->orderBy('customers.account_id')->get();
           $data = $customer->transform(function ($q){

            $productUuid = $q->products?->pluck('Uuid')->toArray();
            $formattedProductUuids = implode(' - ', $productUuid);

             return[
                //
                 'code'=>$q->Uuid,
                 'group_name'=>optional($q->pharmacyGroup)->name,
                 'account_name'=>optional($q->account)->name,
                 'account_type'=>optional($q->account?->accType)->name,
                 'account_class'=>optional($q->account?->class)->name,
                  'doctor_name'=>$q->name,
                 'specialty'=>optional($q->specialty)->name??'',
                 'area'=>optional($q->account?->brick)->name,
                 'class'=>optional($q->class)->name??'',
                    'phone'=>$q->phone,
                    'phone1'=>$q->phone1,
                    'brief'=>$q->brief,
                    'products'=>$formattedProductUuids,
                    'action'=>[
                        "del_account","del_doctor"
                    ],

             ];
         });
        return $data;
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_array($value)) {
            $validation = $cell->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('"'.collect($value)->join(',').'"');
 
            $value = '';
        }
 
        return parent::bindValue($cell, $value);
    }

    public function headings() :array
    {
        return ["CODE","Group Name","Account Name","Account Type" ,"Account Class","Doctor Name","Specialty",  "Area", "Class","Phone","Phone1","Brief","Product_items","Action"];
    }

	 public function registerEvents(): array
    {
        $styleArray = ['font' => ['bold' => true]];

        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->getSheet()->getDelegate()->getStyle('A1:AK1')->getFont()->setName('Calibri')->setSize(15);
                $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(17);
                $event->sheet->getDelegate()->getStyle('A1:AK1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK);
                $event->sheet->getDelegate()->getStyle('N1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);

                // $event->sheet->getDelegate()->getStyle('A1:AK1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                // ->getStartColor()->set('FFFFF');
                foreach ($this->coloumns() as $charachter) {
					$width_value = in_array($charachter,['A'] ) ? 15 : (in_array($charachter,['B','D','M'] ) ? 50 : 20);
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
