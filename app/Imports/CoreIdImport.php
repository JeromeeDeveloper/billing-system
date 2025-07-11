<?php

namespace App\Imports;

use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoreIdImport implements ToCollection, WithHeadingRow
{
    protected $results = [];
    protected $stats = [
        'matched' => 0,
        'unmatched' => 0,
        'inserted' => 0,
        'updated' => 0,
        'removed' => 0
    ];

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            // Validate that the file has the correct header
            if ($rows->isEmpty()) {
                throw new \Exception('Uploaded file is empty.');
            }

            $firstRow = $rows->first();

            // Check for different possible header names
            $cidColumn = null;
            if (isset($firstRow['coreid'])) {
                $cidColumn = 'coreid';
            } elseif (isset($firstRow['customer_no'])) {
                $cidColumn = 'customer_no';
            } elseif (isset($firstRow['customer_no_'])) {
                $cidColumn = 'customer_no_';
            } elseif (isset($firstRow['customer_no'])) {
                $cidColumn = 'customer_no';
            } else {
                // Try to find any column that might contain the CID data
                $possibleColumns = ['coreid', 'customer_no', 'customer_no_', 'cid', 'customer_number', 'customer_id'];
                foreach ($possibleColumns as $column) {
                    if (isset($firstRow[$column])) {
                        $cidColumn = $column;
                        break;
                    }
                }

                if (!$cidColumn) {
                    throw new \Exception('File must have "CoreID" or "Customer No" header in the first column. Available columns: ' . implode(', ', array_keys($firstRow->toArray())));
                }
            }

            // Get all existing CIDs from the uploaded file
            $uploadedCids = $rows->pluck($cidColumn)->map(function($cid) {
                return str_pad($cid, 9, '0', STR_PAD_LEFT);
            })->toArray();

            // Create a set of uploaded CIDs for faster lookup
            $uploadedCidsSet = array_flip($uploadedCids);

            // Process each row in the uploaded file
            foreach ($rows as $row) {
                $cid = str_pad($row[$cidColumn], 9, '0', STR_PAD_LEFT);

                // Find member with this CID
                $member = Member::where('cid', $cid)->first();

                if ($member) {
                    // Update existing member's tagging to PGB
                    $member->update(['member_tagging' => 'PGB']);
                    $this->stats['matched']++;
                    $this->stats['updated']++;

                    $this->results[] = [
                        'cid' => $cid,
                        'status' => 'success',
                        'message' => 'Member found and updated to PGB',
                        'action' => 'updated'
                    ];
                } else {
                    // Insert new member with PGB tagging
                    Member::create([
                        'cid' => $cid,
                        'member_tagging' => 'PGB'
                    ]);
                    $this->stats['unmatched']++;
                    $this->stats['inserted']++;

                    $this->results[] = [
                        'cid' => $cid,
                        'status' => 'success',
                        'message' => 'New member created with PGB tagging',
                        'action' => 'inserted'
                    ];
                }
            }

            // Remove members that are not in the uploaded file (except those with 'New' tagging)
            // Use chunked processing to avoid memory issues with large datasets
            $query = Member::whereNotIn('cid', $uploadedCids)
                ->where(function($query) {
                    $query->where('member_tagging', '!=', 'New')
                          ->orWhereNull('member_tagging');
                });

            $totalToRemove = $query->count();
            Log::info('CoreID Import - Members to remove count: ' . $totalToRemove);

            // Process in chunks to avoid memory issues
            $query->chunk(1000, function($members) {
                foreach ($members as $member) {
                Log::info('CoreID Import - Removing member: ' . $member->cid . ' with tagging: ' . $member->member_tagging);

                // Force delete to bypass any soft deletes if they exist
                $deleted = $member->forceDelete();
                Log::info('CoreID Import - Delete result: ' . ($deleted ? 'success' : 'failed'));

                $this->stats['removed']++;

                $this->results[] = [
                    'cid' => $member->cid,
                    'status' => 'removed',
                    'message' => 'Member removed (not in uploaded file)',
                    'action' => 'removed'
                ];
            }
            });

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CoreID Import Error: ' . $e->getMessage());
            throw $e;
        }
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
