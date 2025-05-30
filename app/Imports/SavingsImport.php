<?php

namespace App\Imports;

use App\Models\Member;
use App\Models\Saving;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Str;

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
        foreach ($rows as $row) {
            $rawCid = $row['customer_no'] ?? null;

            if (!$rawCid) continue;

            $cid = str_pad(preg_replace('/\D/', '', $rawCid), 9, '0', STR_PAD_LEFT); // Ensure 9-digit CID

            $member = Member::where('cid', $cid)->first();

            if (!$member) {
                \Log::warning("Savings Import skipped: Member not found for CID $cid");
                continue;
            }

            $accountNumber = trim($row['account_no'] ?? '');
            if (!$accountNumber) continue;

            Saving::updateOrCreate(
                ['account_number' => $accountNumber],
                [
                    'member_id'         => $member->id,
                    'product_code'      => $row['product_code'] ?? null,
                    'open_date'         => $this->parseDate($row['open_date'] ?? null),
                    'current_balance'   => $this->parseAmount($row['current_bal']),
                    'available_balance' => $this->parseAmount($row['available_bal']),
                    'interest'          => $this->parseAmount($row['interest']),
                ]
            );
        }
    }

    private function parseDate($value)
    {
        try {
            return Carbon::createFromFormat('m/d/Y', $value);
        } catch (\Exception $e) {
            \Log::error("Savings Import: Invalid date format - $value");
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
