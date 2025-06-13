<?php

namespace App\Imports;

use App\Models\Member;
use App\Models\Remittance;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class ShareRemittanceImport implements ToCollection, WithHeadingRow
{
    protected $results = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $empId = $row['empid'] ?? null;
            $name = $row['name'] ?? '';
            $shareAmount = floatval($row['share'] ?? 0);

            if (!$name || $shareAmount <= 0) {
                continue;
            }

            // Split name into lastname, firstname
            $nameParts = explode(',', $name);
            if (count($nameParts) !== 2) {
                continue;
            }

            $lastName = trim($nameParts[0]);
            $firstName = trim($nameParts[1]);

            // Find member by name only if EmpId is null
            $member = null;
            if ($empId) {
                $member = Member::where('emp_id', $empId)
                    ->where('fname', 'like', "%{$firstName}%")
                    ->where('lname', 'like', "%{$lastName}%")
                    ->first();
            } else {
                $member = Member::where('fname', 'like', "%{$firstName}%")
                    ->where('lname', 'like', "%{$lastName}%")
                    ->first();
            }

            if ($member) {
                // Store in remittance table with share_dep
                Remittance::create([
                    'member_id' => $member->id,
                    'branch_id' => $member->branch_id,
                    'share_dep' => $shareAmount,
                ]);

                // Add to results for preview
                $this->results[] = [
                    'emp_id' => $empId,
                    'name' => $name,
                    'member_id' => $member->id,
                    'share_dep' => $shareAmount,
                    'status' => 'success',
                    'message' => 'Member matched and share deposit recorded.',
                ];
            } else {
                // Log unmatched members for debugging
                Log::info('Unmatched member:', ['emp_id' => $empId, 'name' => $name]);

                // Add to results for preview (unmatched)
                $this->results[] = [
                    'emp_id' => $empId,
                    'name' => $name,
                    'member_id' => null,
                    'share_dep' => 0,
                    'status' => 'error',
                    'message' => 'No matching member found for the share deposit.',
                ];
            }
        }
    }

    public function getResults()
    {
        return $this->results;
    }
}
