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

class SavingsImport implements ToCollection, WithHeadingRow
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
            $product = SavingProduct::firstOrCreate(
                ['product_code' => $productCode],
                [
                    'product_name' => "Savings Product $productCode"

                ]
            );

            $accountNumber = trim($row['account_no'] ?? '');
            if (!$accountNumber) {
                $skipped++;
                continue;
            }

            // Check if record already exists
            $exists = Saving::where([
                'account_number' => $accountNumber,
                'member_id' => $member->id,
                'product_code' => $productCode
            ])->exists();

            // Create or update saving with product details
            Saving::updateOrCreate(
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
                    'interest' => $this->parseAmount($row['interest'])
                ]
            );

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

        Log::info("Savings Import Summary: Processed $processed new records, Updated $updated records, Skipped $skipped records");
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

        // Remove commas and cast to float
        $clean = str_replace(',', '', $value);

        return is_numeric($clean) ? floatval($clean) : null;
    }
}
