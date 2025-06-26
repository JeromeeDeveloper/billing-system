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

            $cid = $row['customer_no'];
            [$lname, $fname] = array_map('trim', explode(',', $row['customer_name'] . ','));

            $member = Member::where('cid', $cid)->first();

            if ($member) {
                $member->update([
                    'birth_date'               => $this->parseDate($row['birth_date']),
                    'date_registered'         => $this->parseDate($row['date_registered']),
                    'gender'                  => $this->normalizeGender($row['gender']),
                    'customer_type'           => $row['customer_type'],
                    'customer_classification' => $row['customer_classification'],
                    'industry'                => $row['industry'],
                    'area_officer'            => $row['area_officer'],
                    'area'                    => $row['area'],
                    'status'                  => strtolower($row['status']) === 'merged' ? 'merged' : 'active',
                    'address'                 => $row['address'],
                    'billing_period'          => $this->billingPeriod, // Added billing period here
                ]);
            }
            // Do nothing if member doesn't exist
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
