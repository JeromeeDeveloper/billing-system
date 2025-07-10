<?php

namespace App\Exports;

use App\Models\LoanProduct;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LoanProductsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return LoanProduct::all(['product', 'product_code', 'prioritization', 'billing_type']);
    }

    public function headings(): array
    {
        return [
            'Product',
            'Product Code',
            'Prioritization',
            'Billing Type',
        ];
    }
}
