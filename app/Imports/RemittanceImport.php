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
use App\Models\RemittanceBatch;
use Illuminate\Support\Facades\Auth;

class RemittanceImport implements ToCollection, WithHeadingRow
{
    protected $results = [];
    protected $stats = [
        'matched' => 0,
        'unmatched' => 0,
        'total_amount' => 0
    ];

    protected $savingProducts;
    protected $batch_id;
    protected $imported_at;
    protected $billingPeriod;
    protected $remittance_tag;
    protected $billingType;

    public function __construct($billingPeriod = null, $billingType = 'regular')
    {
        // Load all saving products
        $this->savingProducts = SavingProduct::all();
        $this->billingPeriod = $billingPeriod;
        $this->billingType = $billingType;
    }

    public function collection(Collection $rows)
    {
        $this->batch_id = (string) Str::uuid();
        $this->imported_at = now();
        // Use RemittanceBatch to determine next remittance_tag for this billing period
        $maxTag = RemittanceBatch::where('billing_period', $this->billingPeriod)->max('remittance_tag');
        $this->remittance_tag = $maxTag ? $maxTag + 1 : 1;
        // Insert new batch row
        RemittanceBatch::create([
            'billing_period' => $this->billingPeriod,
            'remittance_tag' => $this->remittance_tag,
            'imported_at' => $this->imported_at,
        ]);
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
        $cidRaw = trim($row['cid'] ?? '');
        $cid = str_pad($cidRaw, 9, '0', STR_PAD_LEFT);
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

        // Find member by 9-digit padded CID only
        $member = Member::where('cid', $cid)->first();

        // Prepare result array with basic info
        $result = [
            'cid' => $cid,
            'name' => $member ? trim(($member->fname ?? '') . ' ' . ($member->lname ?? '')) : '',
            'member_id' => $member ? $member->id : null,
            'loans' => $loans,
            'savings_total' => $savingsTotal,
            'status' => 'error',
            'message' => '',
            'savings_distribution' => []
        ];

        $distributionDetails = [];

        // If member found, save remittance and savings
        if ($member) {
            $result['status'] = 'success';
            $result['message'] = 'Successfully processed.'; // Default success message

            try {
                DB::beginTransaction();

                // Process savings distribution if there's a total amount
                if ($savingsTotal > 0) {
                    $remainingSavings = $savingsTotal;

                    // Get all savings accounts with deduction_amount > 0, sorted by prioritization, excluding mortuary
                    $savingsAccounts = $member->savings()
                        ->where('account_status', 'deduction')
                        ->where('deduction_amount', '>', 0)
                        ->with('savingProduct')
                        ->get()
                        ->filter(function($saving) {
                            // Exclude mortuary by product_type
                            return !($saving->savingProduct && $saving->savingProduct->product_type === 'mortuary');
                        })
                        ->sortBy(function($saving) {
                            $priority = $saving->savingProduct ? $saving->savingProduct->prioritization : 999;
                            $deductionAmount = $saving->deduction_amount ?? 0;
                            $id = $saving->id;
                            $deductionForSorting = $deductionAmount === null ? 0 : $deductionAmount;
                            return [$priority, -$deductionForSorting, $id];
                        });

                    // Distribute amounts based on deduction_amount and prioritization
                    foreach ($savingsAccounts as $savings) {
                        if ($remainingSavings <= 0) break;
                        $deductionAmount = $savings->deduction_amount ?? 0;
                        $amountToApply = min($deductionAmount, $remainingSavings);
                        if ($amountToApply > 0) {
                            $savings->remittance_amount = $amountToApply;
                            $savings->save();
                            $distributionDetails[] = [
                                'product' => $savings->product_name,
                                'product_type' => $savings->savingProduct->product_type ?? null,
                                'amount' => $amountToApply,
                                'deduction_amount' => $deductionAmount
                            ];
                            $remainingSavings -= $amountToApply;
                        }
                    }

                    // If there's remaining amount, apply it to Regular Savings and add to distribution
                    if ($remainingSavings > 0) {
                        $regularSavings = $member->savings()
                            ->whereHas('savingProduct', function($q) {
                                $q->where('product_type', 'regular');
                            })
                            ->first();
                        if ($regularSavings) {
                            $currentRemittance = $regularSavings->remittance_amount ?? 0;
                            $regularSavings->remittance_amount = $currentRemittance + $remainingSavings;
                            $regularSavings->save();
                            $distributionDetails[] = [
                                'product' => $regularSavings->product_name ?? 'Regular Savings',
                                'product_type' => $regularSavings->savingProduct->product_type ?? null,
                                'amount' => $remainingSavings,
                                'deduction_amount' => 0,
                                'is_remaining' => true
                            ];
                            Log::info("[RemittanceImport] Remaining savings remittance of {$remainingSavings} deposited to regular savings for member {$member->id}");
                        }
                    }
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

                    // Get all loan forecasts and sort them by product prioritization, then by principal (desc), then by created_at (asc)
                    $forecasts = collect($member->loanForecasts)->map(function($forecast) use ($member) {
                        // Extract product code from loan_acct_no (e.g., 40102 from 0304-001-40102-000023-3)
                        $productCode = explode('-', $forecast->loan_acct_no)[2] ?? null;

                        // Find the loan product for this member with matching product code
                        $loanProduct = $member->loanProducts()
                            ->where('product_code', $productCode)
                            ->first();

                        // Skip if billing_type is 'not_billed'
                        if ($loanProduct && $loanProduct->billing_type === 'not_billed') {
                            return null;
                        }
                        // Skip if billing_type does not match selected type
                        if ($loanProduct && $this->billingType && $loanProduct->billing_type !== $this->billingType) {
                            return null;
                        }

                        return [
                            'forecast' => $forecast,
                            'prioritization' => $loanProduct ? $loanProduct->prioritization : 999,
                            'product_code' => $productCode,
                            'total_due' => $forecast->total_due,
                            'principal' => $forecast->principal ?? 0,
                            'created_at' => $forecast->created_at,
                        ];
                    })
                    ->filter() // Remove nulls (skipped not_billed or not matching type)
                    ->sort(function($a, $b) {
                        // Sort by prioritization (asc)
                        if ($a['prioritization'] !== $b['prioritization']) {
                            return $a['prioritization'] <=> $b['prioritization'];
                        }
                        // If same priority, sort by principal (desc)
                        if ($a['principal'] !== $b['principal']) {
                            return $b['principal'] <=> $a['principal'];
                        }
                        // If still tied, sort by created_at (asc)
                        return $a['created_at'] <=> $b['created_at'];
                    });

                    // Log the sorted forecasts for debugging
                    Log::info('Sorted forecasts for member ' . $member->id . ':');
                    foreach ($forecasts as $f) {
                        Log::info("Loan Account: {$f['forecast']->loan_acct_no}, Priority: {$f['prioritization']}, Principal: {$f['principal']}, Total Due: {$f['total_due']}");
                    }

                    foreach ($forecasts as $forecastData) {
                        if ($remainingPayment <= 0) break;

                        $forecast = $forecastData['forecast'];
                        $interestDue = $forecast->interest_due ?? 0;
                        $principalDue = $forecast->principal_due ?? 0;
                        $deductedInterest = 0;
                        $deductedPrincipal = 0;

                        // Deduct from interest_due first
                        if ($interestDue > 0) {
                            $deduct = min($remainingPayment, $interestDue);
                            $deductedInterest = $deduct;
                            $interestDue -= $deduct;
                            $remainingPayment -= $deduct;
                        }
                        // Then deduct from principal_due
                        if ($remainingPayment > 0 && $principalDue > 0) {
                            $deduct = min($remainingPayment, $principalDue);
                            $deductedPrincipal = $deduct;
                            $principalDue -= $deduct;
                            $remainingPayment -= $deduct;
                        }

                        // Update the forecast in the database
                        $forecast->update([
                            'interest_due' => $interestDue,
                            'principal_due' => $principalDue,
                            'total_due' => max(0, $principalDue + $interestDue),
                            'total_due_after_remittance' => $deductedPrincipal + $deductedInterest
                        ]);
                        // Set per-field status
                        $forecast->refresh();
                        $forecast->interest_due_status = floatval($forecast->interest_due) === 0.0 ? 'paid' : 'unpaid';
                        $forecast->principal_due_status = floatval($forecast->principal_due) === 0.0 ? 'paid' : 'unpaid';
                        $forecast->total_due_status = floatval($forecast->total_due) === 0.0 ? 'paid' : 'unpaid';
                        $forecast->save();

                        // Create a LoanRemittance record for this deduction
                        \App\Models\LoanRemittance::create([
                            'loan_forecast_id' => $forecast->id,
                            'member_id' => $member->id,
                            'remitted_amount' => $deductedPrincipal + $deductedInterest,
                            'applied_to_interest' => $deductedInterest,
                            'applied_to_principal' => $deductedPrincipal,
                            'remaining_interest_due' => $interestDue,
                            'remaining_principal_due' => $principalDue,
                            'remaining_total_due' => max(0, $principalDue + $interestDue),
                            'remittance_date' => now()->toDateString(),
                            'batch_id' => $this->batch_id,
                            'imported_at' => $this->imported_at,
                            'remittance_tag' => $this->remittance_tag,
                            'billing_period' => $this->billingPeriod,
                        ]);
                    }

                    // If there's still remaining payment, deposit it to regular savings
                    if ($remainingPayment > 0) {
                        $regularSavings = $member->savings()
                            ->whereHas('savingProduct', function($q) {
                                $q->where('product_type', 'regular');
                            })
                            ->first();
                        if ($regularSavings) {
                            $currentRemittance = $regularSavings->remittance_amount ?? 0;
                            $regularSavings->remittance_amount = $currentRemittance + $remainingPayment;
                            $regularSavings->save();
                            $distributionDetails[] = [
                                'product' => $regularSavings->product_name ?? 'Regular Savings',
                                'product_type' => $regularSavings->savingProduct->product_type ?? null,
                                'amount' => $remainingPayment,
                                'deduction_amount' => 0,
                                'is_remaining' => true
                            ];
                            Log::info("[RemittanceImport] Remaining loan payment of {$remainingPayment} deposited to regular savings for member {$member->id}");
                        } else {
                            Log::warning("[RemittanceImport] No regular savings account found for remaining loan payment for member {$member->id}");
                        }
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

                // Always include distributionDetails in the result for export
                $result['savings_distribution'] = $distributionDetails;

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

    public function getResults()
    {
        return $this->results;
    }

    public function getStats()
    {
        return $this->stats;
    }
}
