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
        $cidRaw = trim($row['cid'] ?? '');
        $cid = str_pad($cidRaw, 9, '0', STR_PAD_LEFT);
        $fullName = trim($row['name'] ?? '');
        $share = floatval(str_replace(',', '', $row['share'] ?? 0));

        Log::info('Processing share remittance row:', [
            'cid' => $cid,
            'name' => $fullName,
            'share' => $share
        ]);

        // Match member by CID only
        $member = null;
        if ($cid) {
            $member = Member::where('cid', $cid)->first();
            if ($member) {
                Log::info('Found member by cid: ' . $cid);
            }
        }

        // Prepare result array with basic info
        $result = [
            'cid' => $cid,
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
                    // Log the update
                    Log::info('Updating existing share remittance:', [
                        'member_id' => $member->id,
                        'old_share_dep' => $existingRemittance->share_dep,
                        'new_share_dep' => $share
                    ]);

                    // Update existing record
                    $existingRemittance->update([
                        'share_dep' => $share
                    ]);

                    $result['message'] = "Updated share amount for member: {$member->fname} {$member->lname}";
                } else {
                    // Log the creation
                    Log::info('Creating new share remittance:', [
                        'member_id' => $member->id,
                        'share_dep' => $share
                    ]);

                    // Create new remittance record
                    Remittance::create([
                        'member_id' => $member->id,
                        'branch_id' => $member->branch_id,
                        'loan_payment' => 0,
                        'savings_dep' => 0,
                        'share_dep' => $share
                    ]);

                    $result['message'] = "Created new share record for member: {$member->fname} {$member->lname}";
                }

                DB::commit();
                $result['status'] = 'success';
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error processing share remittance:', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage()
                ]);
                $result['message'] = 'Error processing record: ' . $e->getMessage();
            }
        } else {
            Log::warning('Member not found for share remittance:', [
                'cid' => $cid,
                'name' => $fullName
            ]);
            $result['message'] = "Member not found. Tried matching CID: $cid";
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
