<?php

namespace App\Imports;

use App\Models\SpecialBilling;
use App\Models\LoanProduct;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class SpecialBillingImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // Get all loan products with prioritization
        $loanProducts = LoanProduct::pluck('prioritization', 'product_code')->toArray();

        // Filter rows to only include those with loan prioritization and Bonus products
        $filteredRows = $rows->filter(function ($row) use ($loanProducts) {
            // Check if the row has a product code that exists in loan products
            $productCode = $row['product_code'] ?? $row['product'] ?? null;

            // Only include if product code exists in loan products (has prioritization)
            // AND the product is "Bonus"
            return isset($loanProducts[$productCode]) &&
                   ($row['product'] ?? '') === 'Bonus';
        });

        $grouped = $filteredRows->groupBy(function ($row) {
            return $row['employee_id'] ?? $row['emp_id'] ?? null;
        });

        foreach ($grouped as $employeeId => $employeeRows) {
            $first = $employeeRows->first();
            $name = $first['name'] ?? null;
            if (empty($employeeId) || empty($name)) {
                continue; // skip invalid rows
            }
            $totalAmortization = $employeeRows->sum(function ($row) {
                return floatval($row['amortization'] ?? 0);
            });

            SpecialBilling::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                ],
                [
                    'name'         => $name,
                    'amortization' => $totalAmortization,
                    'start_date'   => $first['start_date'] ?? null,
                    'end_date'     => $first['end_date'] ?? null,
                    'gross'        => $first['gross'] ?? 0,
                    'office'       => $first['office'] ?? null,
                ]
            );
        }
    }
}
