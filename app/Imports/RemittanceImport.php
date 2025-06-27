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
            $totalAmount = floatval(str_replace(',', '', $row['loans'] ?? 0));
            foreach ($this->savingProducts as $product) {
                $columnName = strtolower(str_replace(' ', '_', $product->product_name));
                $totalAmount += floatval(str_replace(',', '', $row[$columnName] ?? 0));
            }
            $this->stats['total_amount'] += $totalAmount;
        }
    }

    protected function processRow($row)
    {
        // Extract and clean data
        $empId = trim($row['empid'] ?? '');
        $fullName = trim($row['name'] ?? '');
        $loans = floatval(str_replace(',', '', $row['loans'] ?? 0));

        // Try different possible savings column names
        $savingsTotal = 0;
        if (isset($row['savings'])) {
            $savingsTotal = floatval(str_replace(',', '', $row['savings'] ?? 0));
        } elseif (isset($row['Savings'])) {
            $savingsTotal = floatval(str_replace(',', '', $row['Savings'] ?? 0));
        } elseif (isset($row['SAVINGS'])) {
            $savingsTotal = floatval(str_replace(',', '', $row['SAVINGS'] ?? 0));
        } else {
            // Debug: Log what columns are available
            Log::warning('Savings column not found. Available columns:', $row->keys()->toArray());
        }

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
            'savings_total' => $savingsTotal,
            'status' => 'error',
            'message' => '',
            'savings_distribution' => []
        ];

        // If member found, save remittance and savings
        if ($member) {
            $result['status'] = 'success';
            $result['message'] = 'Successfully processed.'; // Default success message

            try {
                DB::beginTransaction();

                // Process savings distribution if there's a total amount
                if ($savingsTotal > 0) {
                    $remainingSavings = $savingsTotal;
                    $distributionDetails = [];

                    // Get all savings accounts with deduction_amount > 0, sorted by prioritization
                    $savingsAccounts = $member->savings()
                        ->where('account_status', 'deduction')
                        ->where('deduction_amount', '>', 0)
                        ->with('savingProduct')
                        ->get()
                        ->sortBy(function($saving) {
                            return $saving->savingProduct ? $saving->savingProduct->prioritization : 999;
                        });

                    // Distribute amounts based on deduction_amount and prioritization
                    foreach ($savingsAccounts as $savings) {
                        if ($remainingSavings <= 0) break;

                        $deductionAmount = $savings->deduction_amount ?? 0;
                        $amountToApply = min($deductionAmount, $remainingSavings);

                        if ($amountToApply > 0) {
                            // Update the savings account with the distributed amount
                            $savings->remittance_amount = $amountToApply;
                            $savings->save();

                            $distributionDetails[] = [
                                'product' => $savings->product_name,
                                'amount' => $amountToApply,
                                'deduction_amount' => $deductionAmount
                            ];

                            $remainingSavings -= $amountToApply;
                        }
                    }

                    // If there's remaining amount, apply it to Regular Savings
                    if ($remainingSavings > 0) {
                        $regularSavings = $member->savings()
                            ->where('account_status', 'deduction')
                            ->whereHas('savingProduct', function($q) {
                                $q->where('product_name', 'Savings Deposit-Regular');
                            })
                            ->first();

                        if ($regularSavings) {
                            // Add to existing remittance amount or set it
                            $currentRemittance = $regularSavings->remittance_amount ?? 0;
                            $regularSavings->remittance_amount = $currentRemittance + $remainingSavings;
                            $regularSavings->save();

                            $distributionDetails[] = [
                                'product' => 'Savings Deposit-Regular (Remaining)',
                                'amount' => $remainingSavings,
                                'deduction_amount' => 0
                            ];
                        }
                    }

                    $result['savings_distribution'] = $distributionDetails;
                }

                // Process loan payments and deductions
                if ($loans > 0) {
                    $remainingPayment = $loans;

                    // Reset total_due_after_remittance to 0 for all forecasts
                    // This ensures we start fresh when re-uploading
                    foreach ($member->loanForecasts as $forecast) {
                        $forecast->update([
                            'total_due_after_remittance' => 0
                        ]);
                    }

                    // Get all loan forecasts and sort them by product prioritization
                    $forecasts = collect($member->loanForecasts)->map(function($forecast) use ($member) {
                        // Extract product code from loan_acct_no (e.g., 40102 from 0304-001-40102-000023-3)
                        $productCode = explode('-', $forecast->loan_acct_no)[2] ?? null;

                        // Find the loan product member with matching product code
                        $loanProductMember = $member->loanProductMembers()
                            ->whereHas('loanProduct', function($query) use ($productCode) {
                                $query->where('product_code', $productCode);
                            })
                            ->first();

                        return [
                            'forecast' => $forecast,
                            'prioritization' => $loanProductMember ? $loanProductMember->prioritization : 999,
                            'product_code' => $productCode,
                            'total_due' => $forecast->total_due,
                            'principal' => $forecast->principal ?? 0
                        ];
                    })->sortBy([
                        ['prioritization', 'asc'],
                        ['principal', 'desc']
                    ]);

                    // Log the sorted forecasts for debugging
                    Log::info('Sorted forecasts for member ' . $member->id . ':');
                    foreach ($forecasts as $f) {
                        Log::info("Loan Account: {$f['forecast']->loan_acct_no}, Priority: {$f['prioritization']}, Principal: {$f['principal']}, Total Due: {$f['total_due']}");
                    }

                    foreach ($forecasts as $forecastData) {
                        if ($remainingPayment <= 0) break;

                        $forecast = $forecastData['forecast'];
                        $totalDue = $forecastData['total_due'];
                        $productCode = $forecastData['product_code'];

                        // Calculate how much to pay for this loan
                        $deductionAmount = min($remainingPayment, $totalDue);

                        if ($productCode && $deductionAmount > 0) {
                            Log::info("Processing payment for member {$member->id}:");
                            Log::info("- Loan Account: {$forecast->loan_acct_no}");
                            Log::info("- Total Due: {$totalDue}");
                            Log::info("- Payment Amount: {$deductionAmount}");
                            Log::info("- Remaining Payment Before: {$remainingPayment}");

                            // Update the total_due in LoanForecast
                            $newTotalDue = $totalDue - $deductionAmount;
                            $forecast->update([
                                'total_due' => max(0, $newTotalDue), // Ensure total_due doesn't go below 0
                                'total_due_after_remittance' => $deductionAmount // Store the actual amount remitted
                            ]);
                            Log::info("- Updated Total Due: {$newTotalDue}");
                            Log::info("- Stored Remittance Amount: {$deductionAmount}");

                            // Subtract the deduction amount from remaining payment
                            $remainingPayment -= $deductionAmount;
                            Log::info("- Remaining Payment After: {$remainingPayment}");

                            // If this loan is fully paid, continue to next loan
                            if ($newTotalDue <= 0) {
                                Log::info("- Loan fully paid, moving to next loan");
                                continue;
                            }
                        }
                    }

                    // Recalculate and update member's total loan balance
                    $totalLoanBalance = $member->loanForecasts()->sum('total_due');
                    $member->update(['loan_balance' => $totalLoanBalance]);
                    Log::info("Updated member {$member->id} total loan balance to: {$totalLoanBalance}");

                    // If there's still remaining payment, log it as unused
                    if ($remainingPayment > 0) {
                        Log::warning("Member {$member->id} has unused loan payment: {$remainingPayment}");
                    }
                }

                // Create remittance record with both loans and total savings
                if ($loans > 0 || $savingsTotal > 0) {
                    // Find existing remittance record for this member today
                    $existingRemittance = Remittance::where('member_id', $member->id)
                        ->whereDate('created_at', now()->toDateString())
                        ->first();

                    if ($existingRemittance) {
                        // Update existing record if amounts are different
                        if ($existingRemittance->loan_payment != $loans || $existingRemittance->savings_dep != $savingsTotal) {
                            $existingRemittance->update([
                                'loan_payment' => $loans,
                                'savings_dep' => $savingsTotal,
                                'share_dep' => 0
                            ]);
                            Log::info('Updated existing remittance for member: ' . $member->id .
                                    ' - Old loan: ' . $existingRemittance->loan_payment .
                                    ', New loan: ' . $loans .
                                    ' - Old savings: ' . $existingRemittance->savings_dep .
                                    ', New savings: ' . $savingsTotal);
                        }
                    } else {
                        // Create new remittance record if none exists
                        Remittance::create([
                            'member_id' => $member->id,
                            'branch_id' => $member->branch_id,
                            'loan_payment' => $loans,
                            'savings_dep' => $savingsTotal,
                            'share_dep' => 0
                        ]);
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                if (str_contains($e->getMessage(), "Field 'account_number' doesn't have a default value")) {
                    // If there's a loan payment, still create the remittance record
                    if ($loans > 0) {
                        try {
                            DB::beginTransaction();

                            // Find existing remittance record for this member today
                            $existingRemittance = Remittance::where('member_id', $member->id)
                                ->whereDate('created_at', now()->toDateString())
                                ->first();

                            if ($existingRemittance) {
                                // Update existing record if loan amount is different
                                if ($existingRemittance->loan_payment != $loans) {
                                    $existingRemittance->update([
                                        'loan_payment' => $loans,
                                        'savings_dep' => 0,
                                        'share_dep' => 0
                                    ]);
                                    Log::info('Updated existing loan-only remittance for member: ' . $member->id .
                                            ' - Old loan: ' . $existingRemittance->loan_payment .
                                            ', New loan: ' . $loans);
                                }
                            } else {
                                // Create new remittance record if none exists
                                Remittance::create([
                                    'member_id' => $member->id,
                                    'branch_id' => $member->branch_id,
                                    'loan_payment' => $loans,
                                    'savings_dep' => 0,
                                    'share_dep' => 0
                                ]);
                            }

                            DB::commit();
                            $result['status'] = 'success';
                            $result['message'] = "Matched with member: {$member->fname} {$member->lname} (Loan payment only - No savings account found)";
                        } catch (\Exception $innerException) {
                            DB::rollBack();
                            $result['message'] = 'Error processing loan payment: ' . $innerException->getMessage();
                        }
                    } else {
                        $result['message'] = "No savings account found for this member. Please create a savings account first.";
                    }
                } else {
                    $result['message'] = 'Error processing record: ' . $e->getMessage();
                }
            }
        } else {
            $result['status'] = 'error';
            $result['message'] = 'Member not found.';
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
