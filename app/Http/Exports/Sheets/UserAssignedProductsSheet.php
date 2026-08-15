<?php

namespace App\Http\Exports\Sheets;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Http\Exports\Concerns\ReferenceSheetStyle;

class UserAssignedProductsSheet implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    use ReferenceSheetStyle;
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function collection()
    {
        return $this->user->products()
            ->select('products.Uuid', 'products.name')
            ->get()
            ->map(fn ($product) => [
                'id'   => $product->Uuid,
                'name' => $product->name,
            ]);
    }

    public function headings(): array
    {
        return ['ID', 'Product Name'];
    }

    public function title(): string
    {
        return 'Products';
    }

     protected function columns(): array
    {
        return ['A','B'];
    }

   
}