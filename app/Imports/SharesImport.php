<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Member;
use App\Models\Shares;
use App\Models\ShareProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SharesImport implements ToCollection, WithHeadingRow
{
    protected string $billingPeriod;

    public function __construct(string $billingPeriod)
    {
        $this->billingPeriod = $billingPeriod;
    }

    public function headingRow(): int
    {
        return 1; // Header is in row 1: A1 to J1
    }

    public function collection(Collection $rows)
    {
        $processed = 0;
        $skipped = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $rawCid = $row['customer_no'] ?? null;
            $productCode = trim($row['product_code'] ?? '');

            if (!$rawCid || !$productCode) {
                $skipped++;
                continue;
            }

            $cid = str_pad(preg_replace('/\D/', '', $rawCid), 9, '0', STR_PAD_LEFT); // Ensure 9-digit CID

            $member = Member::where('cid', $cid)->first();

            if (!$member) {
                $skipped++;
                continue;
            }

            // Check if product exists or create it
            $product = ShareProduct::firstOrCreate(
                ['product_code' => $productCode],
                [
                    'product_name' => "Share Product $productCode"

                ]
            );

            $accountNumber = trim($row['account_no'] ?? '');
            if (!$accountNumber) {
                $skipped++;
                continue;
            }

            // Check if record already exists
            $exists = Shares::where([
                'account_number' => $accountNumber,
                'member_id' => $member->id,
                'product_code' => $productCode
            ])->exists();

            // Create or update share with product details
            Shares::updateOrCreate(
                [
                    'account_number' => $accountNumber,
                    'member_id' => $member->id,
                    'product_code' => $productCode
                ],
                [
                    'product_name' => $product->product_name,
                    'open_date' => $this->parseDate($row['open_date'] ?? null),
                    'current_balance' => $this->parseAmount($row['current_bal']),
                    'available_balance' => $this->parseAmount($row['available_bal']),
                    'interest' => $this->parseAmount($row['interest']),
                ]
            );

            // Create relationship in pivot table if it doesn't exist
            if (!$member->shareProducts()->where('share_product_id', $product->id)->exists()) {
                $member->shareProducts()->attach($product->id);
            }

            if ($exists) {
                $updated++;
            } else {
                $processed++;
            }
        }

        Log::info("Shares Import Summary: Processed $processed new records, Updated $updated records, Skipped $skipped records");
    }

    private function parseDate($value)
    {
        try {
            return Carbon::createFromFormat('m/d/Y', $value);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseAmount($value)
    {
        if (is_null($value)) return null;

        // Remove commas and cast to float (handles negatives too)
        $clean = str_replace(',', '', $value);

        return is_numeric($clean) ? floatval($clean) : null;
    }
}
