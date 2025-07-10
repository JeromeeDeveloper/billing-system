<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Member;
use App\Models\Saving;
use Illuminate\Support\Str;
use App\Models\SavingProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class SavingsImport implements ToCollection, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    protected string $billingPeriod;

    public function __construct(string $billingPeriod)
    {
        $this->billingPeriod = $billingPeriod;
    }

    public function headingRow(): int
    {
        return 6; // Headers are in row 6: A6 to L6
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
        $processed = 0;
        $skipped = 0;
        $updated = 0;
        $mortuaryUpdated = 0;

        // Group by member and product code to ensure only one record per product
        $memberProducts = [];

        foreach ($rows as $row) {
            $rawCid = $row['customer_no'] ?? null;
            $accountNumber = trim($row['account_no'] ?? '');
            $productName = trim($row['product_code'] ?? ''); // This is actually the product name from E6

            // Skip rows that are metadata, headers, or non-data content
            if ($this->shouldSkipRow($rawCid, $accountNumber, $productName)) {
                $skipped++;
                continue;
            }

            if (!$rawCid || !$accountNumber) {
                $skipped++;
                continue;
            }

            // Extract product code from account number (3rd segment)
            // Format: 1002-002-20101-100005-4 -> product_code = 20101
            $accountSegments = explode('-', $accountNumber);
            $productCode = $accountSegments[2] ?? null;

            if (!$productCode) {
                Log::warning("Could not extract product code from account number: $accountNumber");
                $skipped++;
                continue;
            }

            $cid = str_pad($rawCid, 9, '0', STR_PAD_LEFT); // Ensure 9-digit CID

            $member = Member::where('cid', $cid)
                           ->whereIn('member_tagging', ['PGB', 'New'])
                           ->first();

            if (!$member) {
                Log::warning("Member not found or not tagged as PGB/New for CID: $cid (Raw CID: $rawCid)");
                $skipped++;
                continue;
            }

            // Create unique key for member + product code combination
            $memberProductKey = $member->id . '_' . $productCode;

            // Only process if we haven't seen this member-product combination yet
            if (!isset($memberProducts[$memberProductKey])) {
                $memberProducts[$memberProductKey] = [
                    'member_id' => $member->id,
                    'product_code' => $productCode,
                    'product_name' => $productName,
                    'account_number' => $accountNumber,
                    'open_date' => $this->parseDate($row['open_date'] ?? null),
                    'current_balance' => $this->parseAmount($row['current_bal']),
                    'available_balance' => $this->parseAmount($row['available_bal']),
                    'interest' => $this->parseAmount($row['interest_due_amount']),
                    'status' => trim($row['status'] ?? ''),
                    'last_transaction_date' => $this->parseDate($row['last_trn_date'] ?? null)
                ];
            } else {
                // If we already have this member-product combination, keep the one with higher balance
                $existingBalance = $memberProducts[$memberProductKey]['current_balance'] ?? 0;
                $newBalance = $this->parseAmount($row['current_bal']) ?? 0;

                if ($newBalance > $existingBalance) {
                    $memberProducts[$memberProductKey] = [
                        'member_id' => $member->id,
                        'product_code' => $productCode,
                        'product_name' => $productName,
                        'account_number' => $accountNumber,
                        'open_date' => $this->parseDate($row['open_date'] ?? null),
                        'current_balance' => $newBalance,
                        'available_balance' => $this->parseAmount($row['available_bal']),
                        'interest' => $this->parseAmount($row['interest_due_amount']),
                        'status' => trim($row['status'] ?? ''),
                        'last_transaction_date' => $this->parseDate($row['last_trn_date'] ?? null)
                    ];
                }
            }
        }

        // Process the unique member-product combinations
        foreach ($memberProducts as $memberProductKey => $data) {
            // Get the member again to ensure it exists
            $member = Member::find($data['member_id']);

            if (!$member) {
                Log::warning("Member not found for ID: {$data['member_id']}");
                $skipped++;
                continue;
            }

            // Check if product exists or create it
            $product = SavingProduct::firstOrCreate(
                ['product_code' => $data['product_code']],
                [
                    'product_name' => $data['product_name'] ?: "Savings Product {$data['product_code']}"
                ]
            );

            // Check if record already exists
            $exists = Saving::where([
                'member_id' => $data['member_id'],
                'product_code' => $data['product_code']
            ])->exists();

            // Create or update saving with product details
            Saving::updateOrCreate(
                [
                    'member_id' => $data['member_id'],
                    'product_code' => $data['product_code']
                ],
                [
                    'account_number' => $data['account_number'],
                    'product_name' => $product->product_name,
                    'open_date' => $data['open_date'],
                    'current_balance' => $data['current_balance'],
                    'available_balance' => $data['available_balance'],
                    'interest' => $data['interest'],
                    'status' => $data['status'],
                    'last_transaction_date' => $data['last_transaction_date']
                ]
            );

            // Check if this is a mortuary product and update deduction_amount
            // This logic automatically sets deduction_amount for mortuary accounts based on the amount_to_deduct
            // from the SavingProduct. It now checks the product_type field.
            $mortuaryDeductionAmount = 0;
            if ($product && $product->product_type === 'mortuary' && $product->amount_to_deduct > 0) {
                $mortuaryDeductionAmount = $product->amount_to_deduct;
            }

            // Update deduction_amount if this is a mortuary product
            if ($mortuaryDeductionAmount > 0) {
                Log::info("Detected mortuary product for member {$data['member_id']}, product {$data['product_code']} ({$product->product_name}) with deduction amount: {$mortuaryDeductionAmount}");

                // Update the deduction_amount for this savings account
                Saving::where([
                    'member_id' => $data['member_id'],
                    'product_code' => $data['product_code']
                ])->update([
                    'deduction_amount' => $mortuaryDeductionAmount,
                    'account_status' => 'deduction'
                ]);

                Log::info("Updated mortuary deduction amount for member {$data['member_id']}, product {$data['product_code']}: {$mortuaryDeductionAmount}");
                $mortuaryUpdated++;
            }

            // Create relationship in pivot table if it doesn't exist
            if (!$member->savingProducts()->where('saving_product_id', $product->id)->exists()) {
                $member->savingProducts()->attach($product->id);
            }

            if ($exists) {
                $updated++;
            } else {
                $processed++;
            }
        }

        // Update member savings balances
        $this->updateMemberSavingsBalances();

        Log::info("Savings Import Summary: Processed $processed new records, Updated $updated records, Skipped $skipped records, Mortuary Updated $mortuaryUpdated records");
    }

    /**
     * Update member savings balances after import
     */
    private function updateMemberSavingsBalances()
    {
        $members = Member::whereIn('member_tagging', ['PGB', 'New'])->get();

        foreach ($members as $member) {
            $totalSavingsBalance = Saving::where('member_id', $member->id)
                ->sum('current_balance');

            $member->update(['savings_balance' => $totalSavingsBalance]);
        }

        Log::info("Updated savings balances for " . $members->count() . " members");
    }

    /**
     * Check if a row should be skipped based on metadata patterns
     */
    private function shouldSkipRow($rawCid, $accountNumber, $productName)
    {
        // Convert to string and lowercase for easier pattern matching
        $cidStr = strtolower(trim($rawCid ?? ''));
        $accountStr = strtolower(trim($accountNumber ?? ''));
        $productStr = strtolower(trim($productName ?? ''));

        // Skip rows with metadata patterns
        $metadataPatterns = [
            '[bukidnon government employees mpc]',
            '[list of savings per product (mis)]',
            '[for the period of',
            'bukidnon government employees mpc',
            'list of savings per product',
            'for the period of',
            '2025]',
            '75741',
            'total',
            'subtotal',
            'summary',
            'page',
            'of',
            'prepared by',
            'approved by',
            'date',
            'time',
            'generated',
            'exported',
            'printed'
        ];

        // Check if any field contains metadata patterns
        foreach ($metadataPatterns as $pattern) {
            if (strpos($cidStr, $pattern) !== false ||
                strpos($accountStr, $pattern) !== false ||
                strpos($productStr, $pattern) !== false) {
                return true;
            }
        }

        // Skip rows that start with brackets (metadata)
        if (preg_match('/^\[.*\]$/', $cidStr) ||
            preg_match('/^\[.*\]$/', $accountStr) ||
            preg_match('/^\[.*\]$/', $productStr)) {
            return true;
        }

        // Skip rows that are just numbers without proper format (like 75741, 2025])
        if (preg_match('/^\d+$/', $accountStr) ||
            preg_match('/^\d+\]$/', $accountStr)) {
            return true;
        }

        // Skip empty or whitespace-only rows
        if (empty(trim($cidStr)) && empty(trim($accountStr)) && empty(trim($productStr))) {
            return true;
        }

        // Skip rows that don't have a proper customer number format (should start with digits)
        if (!empty($cidStr) && !preg_match('/^\d/', $cidStr)) {
            return true;
        }

        return false;
    }

    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Handle Excel date numbers
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            }

            // Handle various date formats
            $dateFormats = [
                'm/d/Y',
                'Y-m-d',
                'd/m/Y',
                'Y/m/d',
                'm-d-Y',
                'd-m-Y'
            ];

            foreach ($dateFormats as $format) {
                try {
                    return Carbon::createFromFormat($format, $value);
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Try Carbon's parse as fallback
            return Carbon::parse($value);
        } catch (\Exception $e) {
            Log::warning("Date parse error for value: {$value}");
            return null;
        }
    }

    private function parseAmount($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        // Remove commas and other non-numeric characters except decimal point
        $clean = preg_replace('/[^0-9.-]/', '', str_replace(',', '', $value));

        return is_numeric($clean) ? floatval($clean) : null;
    }
}
