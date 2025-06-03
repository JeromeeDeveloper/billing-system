<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SavingProduct;
use App\Models\ShareProduct;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample saving products
        $savingProducts = [
            [
                'product_name' => 'Regular Savings',
                'product_code' => 'SAV-REG',
                'interest' => 2.5,
                'description' => 'Standard savings account with competitive interest rate'
            ],
            [
                'product_name' => 'High-Yield Savings',
                'product_code' => 'SAV-HY',
                'interest' => 4.0,
                'description' => 'Higher interest rate for maintaining larger balances'
            ],
            [
                'product_name' => 'Student Savings',
                'product_code' => 'SAV-STU',
                'interest' => 3.0,
                'description' => 'Special savings account for students'
            ]
        ];

        foreach ($savingProducts as $product) {
            SavingProduct::create($product);
        }

        // Create sample share products
        $shareProducts = [
            [
                'product_name' => 'Common Shares',
                'product_code' => 'SHR-COM',
                'interest' => 5.0,
                'description' => 'Regular membership shares'
            ],
            [
                'product_name' => 'Preferred Shares',
                'product_code' => 'SHR-PRF',
                'interest' => 7.0,
                'description' => 'Premium membership shares with higher dividends'
            ],
            [
                'product_name' => 'Investment Shares',
                'product_code' => 'SHR-INV',
                'interest' => 6.0,
                'description' => 'Long-term investment shares'
            ]
        ];

        foreach ($shareProducts as $product) {
            ShareProduct::create($product);
        }
    }
}
