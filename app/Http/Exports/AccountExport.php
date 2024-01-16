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
use App\Models\Account;

class AccountExport implements FromCollection, WithHeadings,WithEvents
{
    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */

    public function collection()
    {
          $account = Account::select('accounts.*')->join('acc_type','acc_type.id','=','accounts.acc_type_id')->where('acc_type.is_pharmacy',0)->get();
           $data = $account->transform(function ($q){
             return[
                 'name'=>$q->name,
                 'acc_type'=>optional($q->accType)->name,
                 'area'=>optional($q->brick)->name,
                 'class'=>optional($q->class)->name??'',
				 'phone'=>$q->phone,
				 'phone1'=>$q->phone1,
				 'address'=>$q->address,
				 'lat'=>$q->lat,
				 'lng'=>$q->lng,

             ];
         });
        return $data;
    }

    public function headings() :array
    {
        return ["Account Name", "Account Type", "Area", "Class","Phone","Phone1", "Address","lat","lng"];
    }

	 public function registerEvents(): array
    {
        $styleArray = ['font' => ['bold' => true]];

        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->getSheet()->getDelegate()->getStyle('A1:AK1')->getFont()->setName('Calibri')->setSize(15);
                $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(17);
                $event->sheet->getDelegate()->getStyle('A1:AK1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK);
               // $event->sheet->getDelegate()->getStyle('A1:AK1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                // ->getStartColor()->set('FFFFF');
                foreach ($this->coloumns() as $charachter) {
					$width_value = in_array($charachter,['A','G'] ) ? 50 : 20;
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
