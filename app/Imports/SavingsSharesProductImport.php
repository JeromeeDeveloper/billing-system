<?php

namespace App\Imports;

use App\Models\Member;
use App\Models\SavingProduct;
use App\Models\ShareProduct;
use App\Models\LoanProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SavingsSharesProductImport implements ToCollection
{
    protected $stats = [
        'processed' => 0,
        'savings_updated' => 0,
        'shares_updated' => 0,
        'loans_updated' => 0,
        'skipped' => 0
    ];

    public function collection(Collection $rows)
    {
        if ($rows->count() < 2) {
            Log::warning("File has insufficient rows for processing");
            return;
        }

        // Get header row (row 1)
        $headerRow = $rows->first();

        // Validate header
        if (trim($headerRow[0] ?? '') !== 'CoreID') {
            Log::error("Invalid header. Expected 'CoreID' in A1, got: " . ($headerRow[0] ?? 'empty'));
            throw new \Exception("Invalid file format. Header A1 must be 'CoreID'");
        }

        // Extract product codes from header (starting from column B)
        $productCodes = [];
        for ($i = 1; $i < count($headerRow); $i++) {
            $productCode = trim($headerRow[$i] ?? '');
            if (!empty($productCode)) {
                $productCodes[$i] = $productCode;
                Log::info("Found product code: {$productCode} at column " . ($i + 1));
            }
        }

        if (empty($productCodes)) {
            Log::error("No product codes found in header row");
            throw new \Exception("No product codes found in header row");
        }

        // Process data rows (starting from row 2)
        for ($rowIndex = 1; $rowIndex < count($rows); $rowIndex++) {
            $row = $rows[$rowIndex];

            // Get CID from column A
            $cidRaw = trim($row[0] ?? '');
            if (empty($cidRaw)) {
                continue; // Skip empty rows
            }

            // Pad CID to 9 digits
            $cid = str_pad(ltrim($cidRaw, "'"), 9, '0', STR_PAD_LEFT);

            // Find member with member_tagging "PGB"
            $member = Member::where('cid', $cid)
                           ->where('member_tagging', 'PGB')
                           ->first();

            if (!$member) {
                Log::warning("Skipping CID {$cid} - member not found or not authorized (PGB)");
                $this->stats['skipped']++;
                continue;
            }

            $this->stats['processed']++;
            Log::info("Processing member: {$member->fname} {$member->lname} (CID: {$cid})");

            // Process each product code column
            foreach ($productCodes as $columnIndex => $productCode) {
                $value = trim($row[$columnIndex] ?? '');

                if (empty($value)) {
                    continue; // Skip empty values
                }

                // Check if this is a savings product
                $savingProduct = SavingProduct::where('product_code', $productCode)->first();
                if ($savingProduct) {
                    $this->updateSavingProduct($member, $savingProduct, $value);
                    continue;
                }

                // Check if this is a shares product
                $shareProduct = ShareProduct::where('product_code', $productCode)->first();
                if ($shareProduct) {
                    $this->updateShareProduct($member, $shareProduct, $value);
                    continue;
                }

                // Check if this is a loan product
                $loanProduct = LoanProduct::where('product_code', $productCode)->first();
                if ($loanProduct) {
                    $this->updateLoanProduct($member, $loanProduct, $value);
                    continue;
                }

                Log::warning("Product code {$productCode} not found in savings, shares, or loan products");
            }
        }

        Log::info("Import completed. Stats: " . json_encode($this->stats));
    }

    private function updateSavingProduct($member, $savingProduct, $value)
    {
        try {
            // Find existing saving record for this member and product
            $saving = $member->savings()
                           ->where('product_code', $savingProduct->product_code)
                           ->first();

            if ($saving) {
                // Update existing saving record
                $saving->update([
                    'deduction_amount' => floatval($value),
                    'account_status' => 'deduction'
                ]);
                Log::info("Updated saving product {$savingProduct->product_code} for member {$member->cid} with deduction_amount: {$value}");
            } else {
                // Create new saving record
                $member->savings()->create([
                    'product_code' => $savingProduct->product_code,
                    'account_number' => $savingProduct->product_code . '-' . $member->cid,
                    'current_balance' => 0,
                    'deduction_amount' => floatval($value),
                    'account_status' => 'deduction',
                    'billing_period' => Auth::user()->billing_period ?? null
                ]);
                Log::info("Created new saving product {$savingProduct->product_code} for member {$member->cid} with deduction_amount: {$value}");
            }

            $this->stats['savings_updated']++;
        } catch (\Exception $e) {
            Log::error("Error updating saving product for member {$member->cid}: " . $e->getMessage());
        }
    }

    private function updateShareProduct($member, $shareProduct, $value)
    {
        try {
            // Find existing share record for this member and product
            $share = $member->shares()
                          ->where('product_code', $shareProduct->product_code)
                          ->first();

            if ($share) {
                // Update existing share record
                $share->update([
                    'deduction_amount' => floatval($value),
                    'account_status' => 'deduction'
                ]);
                Log::info("Updated share product {$shareProduct->product_code} for member {$member->cid} with deduction_amount: {$value}");
            } else {
                // Create new share record
                $member->shares()->create([
                    'product_code' => $shareProduct->product_code,
                    'account_number' => $shareProduct->product_code . '-' . $member->cid,
                    'current_balance' => 0,
                    'deduction_amount' => floatval($value),
                    'account_status' => 'deduction',
                    'billing_period' => Auth::user()->billing_period ?? null
                ]);
                Log::info("Created new share product {$shareProduct->product_code} for member {$member->cid} with deduction_amount: {$value}");
            }

            $this->stats['shares_updated']++;
        } catch (\Exception $e) {
            Log::error("Error updating share product for member {$member->cid}: " . $e->getMessage());
        }
    }

    private function updateLoanProduct($member, $loanProduct, $value)
    {
        try {
            // Find existing loan record for this member and product
            $loan = $member->loans()
                          ->where('product_code', $loanProduct->product_code)
                          ->first();

            if ($loan) {
                // Update existing loan record
                $loan->update([
                    'deduction_amount' => floatval($value),
                    'account_status' => 'deduction'
                ]);
                Log::info("Updated loan product {$loanProduct->product_code} for member {$member->cid} with deduction_amount: {$value}");
            } else {
                // Create new loan record
                $member->loans()->create([
                    'product_code' => $loanProduct->product_code,
                    'account_number' => $loanProduct->product_code . '-' . $member->cid,
                    'current_balance' => 0,
                    'deduction_amount' => floatval($value),
                    'account_status' => 'deduction',
                    'billing_period' => Auth::user()->billing_period ?? null
                ]);
                Log::info("Created new loan product {$loanProduct->product_code} for member {$member->cid} with deduction_amount: {$value}");
            }

            $this->stats['loans_updated']++;
        } catch (\Exception $e) {
            Log::error("Error updating loan product for member {$member->cid}: " . $e->getMessage());
        }
    }

    public function getStats()
    {
        return $this->stats;
    }
}
