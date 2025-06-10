<?php

namespace App\Imports;

use App\Models\Member;
use App\Models\Remittance;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

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
        $fullName = trim($row['name'] ?? '');
        $loans = floatval($row['loans'] ?? 0);
        $regularSavings = floatval($row['regular_savings'] ?? 0);
        $savings2 = floatval($row['savings_2'] ?? 0);

        // Try to find member by emp_id first
        $member = Member::where('emp_id', $empId)->first();

        // If not found by emp_id, try to match by name
        if (!$member && $fullName) {
            // Split the full name into parts
            $nameParts = explode(' ', $fullName);

            // Try different name combinations
            $member = $this->findMemberByName($nameParts);
        }

        // Prepare result array
        $result = [
            'emp_id' => $empId,
            'name' => $fullName,
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
                $result['message'] = "Matched with member: {$member->fname} {$member->lname}";
            } catch (\Exception $e) {
                $result['message'] = 'Error saving remittance: ' . $e->getMessage();
            }
        } else {
            $result['message'] = "Member not found. Tried matching: $fullName";
        }

        return $result;
    }

    protected function findMemberByName($nameParts)
    {
        if (count($nameParts) < 2) {
            return null;
        }

        // Try different name combinations
        $possibleCombinations = $this->getNameCombinations($nameParts);

        foreach ($possibleCombinations as $combination) {
            $member = Member::where(function ($query) use ($combination) {
                $query->whereRaw('LOWER(fname) LIKE ?', ['%' . strtolower($combination['fname']) . '%'])
                    ->whereRaw('LOWER(lname) LIKE ?', ['%' . strtolower($combination['lname']) . '%']);
            })->first();

            if ($member) {
                return $member;
            }
        }

        return null;
    }

    protected function getNameCombinations($nameParts)
    {
        $combinations = [];

        // Case 1: First word as fname, rest as lname
        $combinations[] = [
            'fname' => $nameParts[0],
            'lname' => implode(' ', array_slice($nameParts, 1))
        ];

        // Case 2: First two words as fname, rest as lname (if applicable)
        if (count($nameParts) >= 3) {
            $combinations[] = [
                'fname' => implode(' ', array_slice($nameParts, 0, 2)),
                'lname' => implode(' ', array_slice($nameParts, 2))
            ];
        }

        // Case 3: Last word as lname, rest as fname
        $combinations[] = [
            'fname' => implode(' ', array_slice($nameParts, 0, -1)),
            'lname' => end($nameParts)
        ];

        // Case 4: Last two words as lname, rest as fname (if applicable)
        if (count($nameParts) >= 3) {
            $combinations[] = [
                'fname' => implode(' ', array_slice($nameParts, 0, -2)),
                'lname' => implode(' ', array_slice($nameParts, -2))
            ];
        }

        return $combinations;
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
