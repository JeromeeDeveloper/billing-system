<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SavingProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('saving_products')->insert([
            [
                'id' => 1,
                'product_name' => 'Savings Deposit-Regular',
                'product_code' => '20101',
                'product_type' => 'regular',
                'amount_to_deduct' => null,
                'prioritization' => 1,
                'created_at' => '2025-07-03 08:49:30',
                'updated_at' => '2025-07-04 01:25:17',
            ],
            [
                'id' => 2,
                'product_name' => 'Dormant Savings',
                'product_code' => '20198',
                'product_type' => null,
                'amount_to_deduct' => null,
                'prioritization' => null,
                'created_at' => '2025-07-03 08:51:50',
                'updated_at' => '2025-07-03 08:51:50',
            ],
            [
                'id' => 3,
                'product_name' => 'Progressive Savings Deposit',
                'product_code' => '20206',
                'product_type' => null,
                'amount_to_deduct' => null,
                'prioritization' => null,
                'created_at' => '2025-07-03 08:51:55',
                'updated_at' => '2025-07-03 08:51:55',
            ],
            [
                'id' => 4,
                'product_name' => 'Special Savings Deposit',
                'product_code' => '20207',
                'product_type' => null,
                'amount_to_deduct' => null,
                'prioritization' => 3,
                'created_at' => '2025-07-03 08:51:56',
                'updated_at' => '2025-07-03 11:20:37',
            ],
            [
                'id' => 5,
                'product_name' => 'PSD - HOPE A',
                'product_code' => '20208',
                'product_type' => null,
                'amount_to_deduct' => null,
                'prioritization' => 4,
                'created_at' => '2025-07-03 08:53:02',
                'updated_at' => '2025-07-03 11:20:51',
            ],
            [
                'id' => 6,
                'product_name' => 'PSD - HOPE B',
                'product_code' => '20209',
                'product_type' => null,
                'amount_to_deduct' => null,
                'prioritization' => 5,
                'created_at' => '2025-07-03 08:53:07',
                'updated_at' => '2025-07-03 11:21:03',
            ],
            [
                'id' => 7,
                'product_name' => 'PSD - HOPE C',
                'product_code' => '20210',
                'product_type' => null,
                'amount_to_deduct' => null,
                'prioritization' => 6,
                'created_at' => '2025-07-03 08:53:07',
                'updated_at' => '2025-07-03 11:21:22',
            ],
            [
                'id' => 8,
                'product_name' => 'PSD - HEART A',
                'product_code' => '20217',
                'product_type' => null,
                'amount_to_deduct' => null,
                'prioritization' => 7,
                'created_at' => '2025-07-03 08:53:08',
                'updated_at' => '2025-07-03 11:25:08',
            ],
            [
                'id' => 9,
                'product_name' => 'PSD - HEART B',
                'product_code' => '20218',
                'product_type' => null,
                'amount_to_deduct' => null,
                'prioritization' => null,
                'created_at' => '2025-07-03 08:53:08',
                'updated_at' => '2025-07-03 08:53:08',
            ],
            [
                'id' => 10,
                'product_name' => 'PSD - HEART C',
                'product_code' => '20219',
                'product_type' => null,
                'amount_to_deduct' => null,
                'prioritization' => null,
                'created_at' => '2025-07-03 08:53:08',
                'updated_at' => '2025-07-03 08:53:08',
            ],
            [
                'id' => 11,
                'product_name' => 'Double-Up Savings Deposit',
                'product_code' => '20226',
                'product_type' => null,
                'amount_to_deduct' => null,
                'prioritization' => null,
                'created_at' => '2025-07-03 08:53:08',
                'updated_at' => '2025-07-03 08:53:08',
            ],
            [
                'id' => 12,
                'product_name' => 'Special Fund - Mortuary',
                'product_code' => '20313',
                'product_type' => 'mortuary',
                'amount_to_deduct' => 50.00,
                'prioritization' => null,
                'created_at' => '2025-07-03 08:53:08',
                'updated_at' => '2025-07-04 03:22:04',
            ],
            [
                'id' => 13,
                'product_name' => 'Retirement Plan Savings',
                'product_code' => '20320',
                'product_type' => null,
                'amount_to_deduct' => null,
                'prioritization' => null,
                'created_at' => '2025-07-03 08:54:09',
                'updated_at' => '2025-07-03 08:54:09',
            ],
            [
                'id' => 14,
                'product_name' => 'Emergency Savings Fund',
                'product_code' => '20323',
                'product_type' => null,
                'amount_to_deduct' => null,
                'prioritization' => 2,
                'created_at' => '2025-07-03 08:54:14',
                'updated_at' => '2025-07-03 11:20:18',
            ],
            [
                'id' => 15,
                'product_name' => 'PASKO Savings',
                'product_code' => '20324',
                'product_type' => null,
                'amount_to_deduct' => null,
                'prioritization' => null,
                'created_at' => '2025-07-03 08:55:41',
                'updated_at' => '2025-07-03 08:55:41',
            ],
            [
                'id' => 16,
                'product_name' => 'AMoRe Savings',
                'product_code' => '20325',
                'product_type' => null,
                'amount_to_deduct' => null,
                'prioritization' => null,
                'created_at' => '2025-07-03 08:55:41',
                'updated_at' => '2025-07-03 08:55:41',
            ],
        ]);

        // Optional: Reset AUTO_INCREMENT to 17 if needed
        DB::statement('ALTER TABLE saving_products AUTO_INCREMENT = 17;');
    }
}
