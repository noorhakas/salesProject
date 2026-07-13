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
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;


class UserAccountExport extends DefaultValueBinder implements FromCollection, WithHeadings,WithEvents
{
    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */

    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function collection()
    {

          $searchuser = $this->user;
          $customer =  $searchuser->customers()->select('customers.*')->join('accounts','accounts.id','=','customers.account_id')
                        ->orderBy('customers.account_id')->get();
           $data = $customer->transform(function ($q){
             return[
                //
                 'code'=>$q->Uuid,
                 'group_name'=>optional($q->pharmacyGroup)->name,
                 'account_name'=>optional($q->account)->name,
                 'account_type'=>optional($q->account?->accType)->name,
                  'doctor_name'=>$q->name,
                 'specialty'=>optional($q->specialty)->name??'',
                 'area'=>optional($q->account?->brick)->name,
                 'class'=>optional($q->class)->name??'',
                    'phone'=>$q->phone,
                    'phone1'=>$q->phone1,
                    'brief'=>$q->brief,
                   

             ];
         });
        return $data;
    }

    

    public function headings() :array
    {
        return ["CODE","Group Name","Account Name","Account Type","Doctor Name","Specialty",  "Area", "Class","Phone","Phone1","Brief"];
    }

	 public function registerEvents(): array
    {
        $styleArray = ['font' => ['bold' => true]];

        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->getSheet()->getDelegate()->getStyle('A1:AK1')->getFont()->setName('Calibri')->setSize(15);
                $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(17);
                $event->sheet->getDelegate()->getStyle('A1:AK1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK);
                $event->sheet->getDelegate()->getStyle('L1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);

                foreach ($this->coloumns() as $charachter) {
					$width_value = in_array($charachter,['A'] ) ? 15 : (in_array($charachter,['B','D'] ) ? 50 : 20);
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
