<?php

namespace App\Imports;

use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CifImport implements ToCollection, WithChunkReading, WithBatchInserts, WithHeadingRow
{
    protected string $billingPeriod;

    public function __construct(string $billingPeriod)
    {
        $this->billingPeriod = $billingPeriod;
    }

    public function headingRow(): int
    {
        return 4; // A4 to M4 = headers, so data starts at row 5
    }

    /**
     * Process the file in chunks to reduce memory usage
     */
    public function chunkSize(): int
    {
        return 1000; // Process 1000 rows at a time
    }

    /**
     * Batch size for database operations
     */
    public function batchSize(): int
    {
        return 100; // Insert 100 records at a time
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if (empty($row['customer_no']) || empty($row['customer_name'])) {
                continue;
            }

            // Pad customer_no with leading zeros to ensure 9 digits
            $cid = str_pad($row['customer_no'], 9, '0', STR_PAD_LEFT);

            // Only update existing members with member_tagging PGB
            $member = \App\Models\Member::where('cid', $cid)
                ->whereIn('member_tagging', ['PGB', 'New'])
                ->first();

            if (!$member) {
                // Skip if member doesn't exist or doesn't have PGB tagging
                continue;
            }

            // Remove prefixes and split name
            $name = trim($row['customer_name']);
            $name = preg_replace('/^(Mr\.|Mrs\.|Ms\.|Miss\.|Dr\.|MR\.|MRS\.|MS\.|MISS\.|DR\.)\s*/i', '', $name);
            [$lname, $fname] = array_map('trim', explode(',', $name . ','));

            $data = [
                'fname' => $fname,
                'lname' => $lname,
                'birth_date'               => $this->parseDate($row['birth_date'] ?? null),
                'date_registered'         => $this->parseDate($row['date_registered'] ?? null),
                'gender'                  => $this->normalizeGender($row['gender'] ?? null),
                'customer_type'           => $row['customer_type'] ?? null,
                'customer_classification' => $row['customer_classification'] ?? null,
                'industry'                => $row['industry'] ?? null,
                'area_officer'            => $row['area_officer'] ?? null,
                'area'                    => $row['area'] ?? null,
                'status'                  => strtolower($row['status'] ?? '') === 'merged' ? 'merged' : 'active',
                'address'                 => $row['address'] ?? null,
                'billing_period'          => $this->billingPeriod,
            ];

            // Update the existing member
            $member->update($data);

            // Create or update MasterList entry
            try {
                $masterListEntry = \App\Models\MasterList::updateOrCreate(
                    [
                        'member_id' => $member->id,
                        'billing_period' => $this->billingPeriod,
                    ],
                    [
                        'branches_id' => $member->branch_id, // This can be null for members with no branch
                        'status' => 'deduction',
                    ]
                );

                // Log for debugging
                Log::info("CIF Import - Updated member: {$member->cid}, Branch ID: " . ($member->branch_id ?? 'NULL') . ", MasterList ID: {$masterListEntry->id}");
            } catch (\Exception $e) {
                Log::error("CIF Import - Failed to create MasterList for member {$member->cid}: " . $e->getMessage());
            }
        }
    }

    private function parseDate($value)
    {
        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value);
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            Log::error("CIF Date Parse Error: " . $value);
            return null;
        }
    }

    private function normalizeGender($gender)
    {
        return match (strtolower($gender)) {
            'male'   => 'male',
            'female' => 'female',
            default  => 'other',
        };
    }
}
