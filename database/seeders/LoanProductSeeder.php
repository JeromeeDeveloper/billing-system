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
            ['product' => 'Prov. Prep.-Enhanced MPL', 'product_code' => '44101', 'prioritization' => 1],
            ['product' => 'Prov.Loan-Petty Cash', 'product_code' => '44219', 'prioritization' => 2],
            ['product' => 'Prov. Dim.-Enhanced MPL', 'product_code' => '44215', 'prioritization' => 3],
            ['product' => 'Prov Flat-Emergency MPL', 'product_code' => '44301', 'prioritization' => 4],
            ['product' => 'Prov. Flat.-Enhanced MPL', 'product_code' => '44303', 'prioritization' => 5],
            ['product' => 'Prod.Dim - Business Loan', 'product_code' => '42201', 'prioritization' => 6],
            ['product' => 'Prov Prep. -Emergency MPL', 'product_code' => '44109', 'prioritization' => 7],
            ['product' => 'Gadget Loan', 'product_code' => '44203', 'prioritization' => 8],
            ['product' => 'Prov Dim-Emergency MPL', 'product_code' => '44205', 'prioritization' => 9],
            ['product' => 'Auxi. Prep.-Commodity Loan', 'product_code' => '40102', 'prioritization' => 10],
            ['product' => 'Auxi. Dim.-Commodity Loan', 'product_code' => '40202', 'prioritization' => 11],
            ['product' => 'Providential - Prepaid - MBL', 'product_code' => '40101', 'prioritization' => 12],
            ['product' => 'Prov.Prep-Loyalty Loan', 'product_code' => '40103', 'prioritization' => 13],
            ['product' => 'Auxi. Dim.-LAD', 'product_code' => '40201', 'prioritization' => 14],
            ['product' => 'BACK TO BACK LOAN - Dim.', 'product_code' => '40203', 'prioritization' => 15],
            ['product' => 'Prod. Dim.-Livelihood Loan', 'product_code' => '40204', 'prioritization' => 16],
            ['product' => 'Prov. Dim. - MBL', 'product_code' => '40205', 'prioritization' => 17],
            ['product' => 'Rest. Loan - Salary - Dim', 'product_code' => '40501', 'prioritization' => 18],
            ['product' => 'Agribiz Loan - Dim', 'product_code' => '43201', 'prioritization' => 19],
            ['product' => 'Prod. Dim.-Crop Loan', 'product_code' => '43202', 'prioritization' => 20],
            ['product' => 'Prov. Prep. - MedShare', 'product_code' => '44102', 'prioritization' => 21],
            ['product' => 'Prov. Prep. - Mid Year', 'product_code' => '44103', 'prioritization' => 22],
            ['product' => 'Prov. Prep. - Year End', 'product_code' => '44104', 'prioritization' => 23],
            ['product' => 'Prov.Prep-Salary Casual', 'product_code' => '44105', 'prioritization' => 24],
            ['product' => 'Prov.Prep-Appliance Loan', 'product_code' => '44106', 'prioritization' => 25],
            ['product' => 'Prov. Prep- ExtraBonus', 'product_code' => '44107', 'prioritization' => 26],
            ['product' => 'Prov.Prep-Hazard', 'product_code' => '44108', 'prioritization' => 27],
            ['product' => 'Prov.Prep-Salary Loan', 'product_code' => '44111', 'prioritization' => 28],
            ['product' => 'Prov.Prep-Short TL', 'product_code' => '44112', 'prioritization' => 29],
            ['product' => 'Prov.Prep-Subsistence', 'product_code' => '44113', 'prioritization' => 30],
            ['product' => 'Remedial Account - Prepaid', 'product_code' => '44198', 'prioritization' => 31],
            ['product' => 'Conditlional Loan Remedy', 'product_code' => '44202', 'prioritization' => 32],
            ['product' => 'POWER Loan', 'product_code' => '44204', 'prioritization' => 33],
            ['product' => 'Prov. Dim. - Emergency Loan', 'product_code' => '44206', 'prioritization' => 34],
            ['product' => 'Prov. Dim. - 3K Loan', 'product_code' => '44207', 'prioritization' => 35],
            ['product' => 'Insurance Loan', 'product_code' => '44208', 'prioritization' => 36],
            ['product' => 'Prov. Dim. - MedShare', 'product_code' => '44209', 'prioritization' => 37],
            ['product' => 'Prov. Dim. - MidYear', 'product_code' => '44210', 'prioritization' => 38],
            ['product' => 'Prov. Dim. - MPL', 'product_code' => '44211', 'prioritization' => 39],
            ['product' => 'Prov. Dim. - RATA', 'product_code' => '44212', 'prioritization' => 40],
            ['product' => 'Prov. Dim. - STL', 'product_code' => '44213', 'prioritization' => 41],
            ['product' => 'Prov. Dim. - Year End', 'product_code' => '44214', 'prioritization' => 42],
            ['product' => 'Prov. Dim.-Calamity Loan', 'product_code' => '44216', 'prioritization' => 43],
            ['product' => 'Prov. Dim.-Regular MPL', 'product_code' => '44217', 'prioritization' => 44],
            ['product' => 'Prov.Dim.- Salary', 'product_code' => '44218', 'prioritization' => 45],
            ['product' => 'Prov. Dim.- Appliance Loan', 'product_code' => '44220', 'prioritization' => 46],
            ['product' => 'Prov. Dim. - Subsistence', 'product_code' => '44222', 'prioritization' => 47],
            ['product' => 'Prov. Dim.-Memorial Loan', 'product_code' => '44223', 'prioritization' => 48],
            ['product' => 'Prov. Dim. - 3P\'s C3', 'product_code' => '44224', 'prioritization' => 49],
            ['product' => 'Start Up Loan', 'product_code' => '44225', 'prioritization' => 50],
            ['product' => 'Prov. Flat -Salary Loan', 'product_code' => '44304', 'prioritization' => 51],
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
