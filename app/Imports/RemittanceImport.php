<?php

namespace App\Imports;

use App\Models\Member;
use App\Models\Remittance;
use App\Models\SavingProduct;
use App\Models\Savings;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RemittanceImport implements ToCollection, WithHeadingRow
{
    protected $results = [];
    protected $stats = [
        'matched' => 0,
        'unmatched' => 0,
        'total_amount' => 0
    ];

    protected $savingProducts;

    public function __construct()
    {
        // Load all saving products
        $this->savingProducts = SavingProduct::all();
    }

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

            // Calculate total amount including all savings
            $totalAmount = $row['loans'] ?? 0;
            foreach ($this->savingProducts as $product) {
                $columnName = strtolower(str_replace(' ', '_', $product->product_name));
                $totalAmount += floatval($row[$columnName] ?? 0);
            }
            $this->stats['total_amount'] += $totalAmount;
        }
    }

    protected function processRow($row)
    {
        // Extract and clean data
        $empId = trim($row['empid'] ?? '');
        $fullName = trim($row['name'] ?? '');
        $loans = floatval($row['loans'] ?? 0);

        // Try to find member by emp_id first
        $member = Member::where('emp_id', $empId)->first();

        // If not found by emp_id, try to match by name
        if (!$member && $fullName) {
            $nameParts = explode(' ', $fullName);
            $member = $this->findMemberByName($nameParts);
        }

        // Prepare result array with basic info
        $result = [
            'emp_id' => $empId,
            'name' => $fullName,
            'member_id' => $member ? $member->id : null,
            'loans' => $loans,
            'status' => 'error',
            'message' => '',
            'savings' => []
        ];

        // Add savings amounts to result for display
        foreach ($this->savingProducts as $product) {
            $columnName = strtolower(str_replace(' ', '_', $product->product_name));
            $amount = floatval($row[$columnName] ?? 0);
            $result['savings'][$product->product_name] = $amount;
        }

        // If member found, save remittance and savings
        if ($member) {
            try {
                DB::beginTransaction();

                // Process each saving product
                $totalSavings = 0; // Track total savings for this member
                foreach ($this->savingProducts as $product) {
                    $columnName = strtolower(str_replace(' ', '_', $product->product_name));
                    $amount = floatval($row[$columnName] ?? 0);

                    if ($amount > 0) {
                        $totalSavings += $amount; // Add to total savings

                        // Find or create savings account for this product
                        $savings = Savings::firstOrCreate(
                            [
                                'member_id' => $member->id,
                                'product_code' => $product->product_code
                            ],
                            [
                                'product_name' => $product->product_name,

                                'remittance_amount' => $amount
                            ]
                        );

                        // Update savings balance and add to existing deduction amount

                        $savings->remittance_amount = $savings->remittance_amount + $amount;
                        $savings->save();

                        Log::info('Updated savings for member: ' . $member->id .
                                ', product: ' . $product->product_name .
                                ', new amount: ' . $amount .
                                ', total deduction amount: ' . $savings->remittance_amount);
                    }
                }

                // Create remittance record with both loans and total savings
                if ($loans > 0 || $totalSavings > 0) {
                    Remittance::create([
                        'member_id' => $member->id,
                        'branch_id' => $member->branch_id,
                        'loan_payment' => $loans,
                        'savings_dep' => $totalSavings,
                        'share_dep' => 0
                    ]);
                }

                DB::commit();
                $result['status'] = 'success';
                $result['message'] = "Matched with member: {$member->fname} {$member->lname}";
            } catch (\Exception $e) {
                DB::rollBack();
                if (str_contains($e->getMessage(), "Field 'account_number' doesn't have a default value")) {
                    $result['message'] = "No savings account found for this member. Please create a savings account first.";
                } else {
                    $result['message'] = 'Error processing record: ' . $e->getMessage();
                }
            }
        } else {
            $result['message'] = "Member not found. Tried matching: $fullName";
        }

        return $result;
    }

    protected function findMemberByName($nameParts)
    {
        if (count($nameParts) < 2) {
            return null;
        }

        $possibleCombinations = $this->getNameCombinations($nameParts);

        foreach ($possibleCombinations as $combination) {
            $member = Member::where(function ($query) use ($combination) {
                $query->whereRaw('LOWER(fname) LIKE ?', ['%' . strtolower($combination['fname']) . '%'])
                    ->whereRaw('LOWER(lname) LIKE ?', ['%' . strtolower($combination['lname']) . '%']);
            })->first();

            if ($member) {
                return $member;
            }
        }

        return null;
    }

    protected function getNameCombinations($nameParts)
    {
        $combinations = [];

        // Case 1: First word as fname, rest as lname
        $combinations[] = [
            'fname' => $nameParts[0],
            'lname' => implode(' ', array_slice($nameParts, 1))
        ];

        // Case 2: First two words as fname, rest as lname (if applicable)
        if (count($nameParts) >= 3) {
            $combinations[] = [
                'fname' => implode(' ', array_slice($nameParts, 0, 2)),
                'lname' => implode(' ', array_slice($nameParts, 2))
            ];
        }

        // Case 3: Last word as lname, rest as fname
        $combinations[] = [
            'fname' => implode(' ', array_slice($nameParts, 0, -1)),
            'lname' => end($nameParts)
        ];

        // Case 4: Last two words as lname, rest as fname (if applicable)
        if (count($nameParts) >= 3) {
            $combinations[] = [
                'fname' => implode(' ', array_slice($nameParts, 0, -2)),
                'lname' => implode(' ', array_slice($nameParts, -2))
            ];
        }

        return $combinations;
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
