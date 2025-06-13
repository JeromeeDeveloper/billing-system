<?php

namespace App\Imports;

use App\Models\Member;
use App\Models\Remittance;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShareRemittanceImport implements ToCollection, WithHeadingRow
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

            if ($result['status'] === 'success') {
                $this->stats['matched']++;
            } else {
                $this->stats['unmatched']++;
            }

            $this->stats['total_amount'] += floatval(str_replace(',', '', $row['share'] ?? 0));
        }
    }

    protected function processRow($row)
    {
        // Extract and clean data
        $empId = trim($row['empid'] ?? '');
        $fullName = trim($row['name'] ?? '');
        $share = floatval(str_replace(',', '', $row['share'] ?? 0));

        // Try to find member by emp_id first if provided
        $member = null;
        if ($empId) {
            $member = Member::where('emp_id', $empId)->first();
        }

        // If not found by emp_id or emp_id not provided, try to match by name
        if (!$member && $fullName) {
            // Split name into lastname and firstname
            $nameParts = explode(',', $fullName);
            if (count($nameParts) === 2) {
                $lastName = trim($nameParts[0]);
                $firstName = trim($nameParts[1]);

                $member = Member::where(function($query) use ($lastName, $firstName) {
                    $query->whereRaw('LOWER(lname) LIKE ?', ['%' . strtolower($lastName) . '%'])
                          ->whereRaw('LOWER(fname) LIKE ?', ['%' . strtolower($firstName) . '%']);
                })->first();
            }
        }

        // Prepare result array with basic info
        $result = [
            'emp_id' => $empId,
            'name' => $fullName,
            'member_id' => $member ? $member->id : null,
            'share' => $share,
            'status' => 'error',
            'message' => ''
        ];

        // If member found, save remittance
        if ($member) {
            try {
                DB::beginTransaction();

                // Find existing remittance record for this member today
                $existingRemittance = Remittance::where('member_id', $member->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->first();

                if ($existingRemittance) {
                    // Update existing record
                    $existingRemittance->update([
                        'share_dep' => $share
                    ]);
                    Log::info('Updated existing share remittance for member: ' . $member->id .
                            ' - New share amount: ' . $share);
                } else {
                    // Create new remittance record
                    Remittance::create([
                        'member_id' => $member->id,
                        'branch_id' => $member->branch_id,
                        'loan_payment' => 0,
                        'savings_dep' => 0,
                        'share_dep' => $share
                    ]);
                }

                DB::commit();
                $result['status'] = 'success';
                $result['message'] = "Matched with member: {$member->fname} {$member->lname}";
            } catch (\Exception $e) {
                DB::rollBack();
                $result['message'] = 'Error processing record: ' . $e->getMessage();
            }
        } else {
            $result['message'] = "Member not found. Tried matching: $fullName";
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
