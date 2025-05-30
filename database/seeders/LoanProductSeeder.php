<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LoanProduct;

class LoanProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $loanProducts = [
            ['product_code' => '40307', 'product' => 'Salary Loan-Flat', 'prioritization' => 1],
            ['product_code' => '40106', 'product' => 'Livelihood Loan -Prepaid', 'prioritization' => 2],
            ['product_code' => '43305', 'product' => 'Agri PLEA-Flat', 'prioritization' => 3],
            ['product_code' => '43304', 'product' => 'Agri ACPC-Anyo Flat', 'prioritization' => 4],
            ['product_code' => '43302', 'product' => 'Agri Loan-Flat', 'prioritization' => 5],
            ['product_code' => '40208', 'product' => 'SAlary Loan-Diminishing APDS', 'prioritization' => 6],
        ];

        foreach ($loanProducts as $product) {
            LoanProduct::create([
                'product' => $product['product'],
                'product_code' => $product['product_code'],
                'prioritization' => $product['prioritization'],
            ]);
        }
    }
}
