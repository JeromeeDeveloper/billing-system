<?php

namespace App\Imports;

use App\Models\Member;
use App\Models\Branch;
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
        'removed' => 0,
        'branch_not_found' => 0
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
            $branchCodeColumn = null;

            // Check for Customer No column (A1)
            if (isset($firstRow['customer_no'])) {
                $cidColumn = 'customer_no';
            } elseif (isset($firstRow['coreid'])) {
                $cidColumn = 'coreid';
            } elseif (isset($firstRow['customer_no_'])) {
                $cidColumn = 'customer_no_';
            } else {
                // Try to find any column that might contain the CID data
                $possibleColumns = ['customer_no', 'coreid', 'customer_no_', 'cid', 'customer_number', 'customer_id'];
                foreach ($possibleColumns as $column) {
                    if (isset($firstRow[$column])) {
                        $cidColumn = $column;
                        break;
                    }
                }

                if (!$cidColumn) {
                    throw new \Exception('File must have "Customer No" header in column A. Available columns: ' . implode(', ', array_keys($firstRow->toArray())));
                }
            }

            // Check for Branch Code column (B1)
            if (isset($firstRow['branch_code'])) {
                $branchCodeColumn = 'branch_code';
            } elseif (isset($firstRow['branch'])) {
                $branchCodeColumn = 'branch';
            } elseif (isset($firstRow['branchcode'])) {
                $branchCodeColumn = 'branchcode';
            } else {
                // Try to find any column that might contain the branch code data
                $possibleBranchColumns = ['branch_code', 'branch', 'branchcode', 'branch_code_', 'branch_cd'];
                foreach ($possibleBranchColumns as $column) {
                    if (isset($firstRow[$column])) {
                        $branchCodeColumn = $column;
                        break;
                    }
                }
            }

            // Log the detected columns for debugging
            Log::info("CoreID Import - Detected columns: CID='{$cidColumn}', Branch='{$branchCodeColumn}'");

            // Get all existing CIDs from the uploaded file
            $uploadedCids = $rows->pluck($cidColumn)->map(function($cid) {
                return str_pad($cid, 9, '0', STR_PAD_LEFT);
            })->toArray();

            // Create a set of uploaded CIDs for faster lookup
            $uploadedCidsSet = array_flip($uploadedCids);

            // Process each row in the uploaded file
            foreach ($rows as $row) {
                $cid = str_pad($row[$cidColumn], 9, '0', STR_PAD_LEFT);
                $branchCode = $branchCodeColumn ? trim($row[$branchCodeColumn] ?? '') : null;

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
                    // For new members, check if branch exists if branch_code is provided
                    $branchId = null;
                    if ($branchCode) {
                        $branch = Branch::where('code', $branchCode)->first();
                        if ($branch) {
                            $branchId = $branch->id;
                        } else {
                            // Log branch not found and skip this member
                            Log::warning("CoreID Import - Branch with code '{$branchCode}' not found for CID: {$cid}");
                            $this->stats['branch_not_found']++;
                            $this->results[] = [
                                'cid' => $cid,
                                'status' => 'skipped',
                                'message' => "Branch with code '{$branchCode}' not found. Please create branch manually first.",
                                'action' => 'skipped'
                            ];
                            continue;
                        }
                    }

                    // Insert new member with PGB tagging and branch_id if available
                    Member::create([
                        'cid' => $cid,
                        'member_tagging' => 'PGB',
                        'branch_id' => $branchId
                    ]);
                    $this->stats['unmatched']++;
                    $this->stats['inserted']++;

                    $this->results[] = [
                        'cid' => $cid,
                        'status' => 'success',
                        'message' => $branchId ? "New member created with PGB tagging and assigned to branch '{$branchCode}'" : "New member created with PGB tagging (no branch assignment)",
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
