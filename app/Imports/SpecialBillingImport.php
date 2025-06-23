<?php

namespace App\Imports;

use App\Models\SpecialBilling;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class SpecialBillingImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $grouped = $rows->groupBy(function ($row) {
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
                    'total_due'    => $totalAmortization,
                ]
            );
        }
    }
}
