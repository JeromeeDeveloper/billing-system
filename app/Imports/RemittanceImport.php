<?php

namespace App\Imports;

use App\Models\Member;
use App\Models\Remittance;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RemittanceImport implements ToCollection, WithHeadingRow
{
    protected $results = [];
    protected $stats = [
        'matched' => 0,
        'unmatched' => 0,
        'total_amount' => 0
    ];

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $result = $this->processRow($row);
            $this->results[] = $result;

            // Update stats
            if ($result['status'] === 'success') {
                $this->stats['matched']++;
            } else {
                $this->stats['unmatched']++;
            }

            // Update total amount
            $this->stats['total_amount'] += $result['loans'] + $result['regular_savings'] + $result['savings_2'];
        }
    }

    protected function processRow($row)
    {
        // Extract and clean data
        $empId = trim($row['empid'] ?? '');
        $name = trim($row['name'] ?? '');
        $loans = floatval($row['loans'] ?? 0);
        $regularSavings = floatval($row['regular_savings'] ?? 0);
        $savings2 = floatval($row['savings_2'] ?? 0);

        // Try to find member by emp_id
        $member = Member::where('emp_id', $empId)->first();

        // If not found by emp_id, try to match by name
        if (!$member && $name) {
            $nameParts = explode(' ', $name);
            if (count($nameParts) >= 2) {
                $fname = $nameParts[0];
                $lname = implode(' ', array_slice($nameParts, 1));

                $member = Member::where('fname', 'LIKE', "%{$fname}%")
                    ->where('lname', 'LIKE', "%{$lname}%")
                    ->first();
            }
        }

        // Prepare result array
        $result = [
            'emp_id' => $empId,
            'name' => $name,
            'loans' => $loans,
            'regular_savings' => $regularSavings,
            'savings_2' => $savings2,
            'status' => 'error',
            'message' => ''
        ];

        // If member found, save remittance
        if ($member) {
            try {
                Remittance::create([
                    'member_id' => $member->id,
                    'branch_id' => $member->branch_id,
                    'loan_payment' => $loans,
                    'savings_dep' => $regularSavings,
                    'share_dep' => $savings2
                ]);

                $result['status'] = 'success';
                $result['message'] = 'Record processed successfully';
            } catch (\Exception $e) {
                $result['message'] = 'Error saving remittance: ' . $e->getMessage();
            }
        } else {
            $result['message'] = 'Member not found';
        }

        return $result;
    }

    public function getResults()
    {
        return $this->results;
    }

    public function getStats()
    {
        return $this->stats;
    }
}
