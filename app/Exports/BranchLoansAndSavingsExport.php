<?php

namespace App\Exports;

use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Log;
use App\Models\Savings;

class BranchLoansAndSavingsExport implements FromCollection, WithHeadings
{
    protected $remittanceData;
    protected $branch_id;

    public function __construct($remittanceData, $branch_id)
    {
        $this->remittanceData = $remittanceData;
        $this->branch_id = $branch_id;
    }

    public function headings(): array
    {
        return [
            'branch_code',
            'product_code/dr',
            'gl/sl cct no',
            'amt',
            'product_code/cr',
            'gl/sl acct no',
            'amount'
        ];
    }

    public function collection()
    {
        $exportRows = new Collection();

        foreach ($this->remittanceData as $record) {
            if (empty($record->member_id)) {
                continue;
            }

            $member = Member::with(['branch', 'loanForecasts', 'savings.savingProduct'])
                ->where('branch_id', $this->branch_id) // Only get members from this branch
                ->find($record->member_id);

            if (!$member) {
                Log::warning('Member not found or not in branch for record: ' . json_encode($record));
                continue;
            }

            // Verify member belongs to the correct branch
            if ($member->branch_id !== $this->branch_id) {
                Log::warning('Member ' . $member->id . ' does not belong to branch ' . $this->branch_id);
                continue;
            }

            Log::info("Processing member {$member->id} ({$member->fname} {$member->lname}) with " . $member->savings->count() . " savings accounts");
            foreach ($member->savings as $savings) {
                Log::info("Member savings account: {$savings->account_number}, product: " . ($savings->savingProduct ? $savings->savingProduct->product_name : 'N/A'));
            }

            // Handle loan payments
            if ($record->loans > 0) {
                foreach ($member->loanForecasts as $forecast) {
                    if ($forecast->total_due_after_remittance > 0) {
                        $originalAccountNumber = $forecast->loan_acct_no;
                        $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);

                        Log::info("Loan account number transformation: '{$originalAccountNumber}' -> '{$formattedAccountNumber}'");

                        $exportRows->push([
                            'branch_code' => $member->branch->code ?? '',
                            'product_code/dr' => '',
                            'gl/sl cct no' => '',
                            'amt' => '',
                            'product_code/cr' => '4', // Assuming 4 is for Loans
                            'gl/sl acct no' => $formattedAccountNumber,
                            'amount' => number_format($forecast->total_due_after_remittance, 2, '.', '')
                        ]);
                    }
                }
            }

            // Handle savings
            if (!empty($record->savings)) {
                // Handle new savings structure with total and distribution
                $savingsTotal = 0;
                $savingsDistribution = [];

                if (is_array($record->savings) && isset($record->savings['total'])) {
                    // New format: {total: amount, distribution: [...]}
                    $savingsTotal = $record->savings['total'];
                    $savingsDistribution = $record->savings['distribution'] ?? [];
                } elseif (is_array($record->savings)) {
                    // Old format: {product_name: amount, ...}
                    $savingsTotal = collect($record->savings)->sum();
                    foreach ($record->savings as $productName => $amount) {
                        if ($amount > 0) {
                            $savingsDistribution[] = [
                                'product' => $productName,
                                'amount' => $amount
                            ];
                        }
                    }
                }

                if ($savingsTotal > 0) {
                    // Process each distribution entry
                    foreach ($savingsDistribution as $distribution) {
                        $productName = $distribution['product'];
                        $amountFromImport = $distribution['amount'];

                        if ($amountFromImport <= 0) {
                            continue;
                        }

                        // Exclude mortuary products
                        $savingAccount = $member->savings->first(function ($s) use ($productName) {
                            return $s->savingProduct && strtolower($s->savingProduct->product_name) === strtolower($productName);
                        });
                        if ($savingAccount && $savingAccount->savingProduct && $savingAccount->savingProduct->product_type === 'mortuary') {
                            continue;
                        }


                        // Find the specific savings account for this member and product name
                        $savingAccount = $member->savings->first(function ($s) use ($productName) {
                            return $s->savingProduct && strtolower($s->savingProduct->product_name) === strtolower($productName);
                        });

                        if ($savingAccount) {
                            $deductionAmount = $savingAccount->deduction_amount ?? 0;

                            Log::info("Processing savings account for product '{$productName}': {$savingAccount->account_number}");

                            // Add a row for the deduction amount, if it exists
                            if ($deductionAmount > 0) {
                                $originalAccountNumber = $savingAccount->account_number;
                                $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);

                                Log::info("Deduction account number transformation: '{$originalAccountNumber}' -> '{$formattedAccountNumber}'");

                                $exportRows->push([
                                    'branch_code' => $member->branch->code ?? '',
                                    'product_code/dr' => '',
                                    'gl/sl cct no' => '',
                                    'amt' => '',
                                    'product_code/cr' => '1',
                                    'gl/sl acct no' => $formattedAccountNumber,
                                    'amount' => number_format($deductionAmount, 2, '.', '')
                                ]);
                            }

                            // Add a row for the remaining amount from the import to Regular Savings
                            $remainingAmount = $amountFromImport - $deductionAmount;
                            if ($remainingAmount > 0) {
                                // Find regular savings account for this member
                                $regularSavings = $member->savings->first(function ($s) {
                                    return $s->savingProduct && $s->savingProduct->product_type === 'regular';
                                });
                                if ($regularSavings) {
                                    Log::info("Found Savings Deposit-Regular account: {$regularSavings->account_number}");
                                    $originalAccountNumber = $regularSavings->account_number;
                                    $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);

                                    Log::info("Regular savings account number transformation: '{$originalAccountNumber}' -> '{$formattedAccountNumber}'");

                                    $exportRows->push([
                                        'branch_code' => $member->branch->code ?? '',
                                        'product_code/dr' => '',
                                        'gl/sl cct no' => '',
                                        'amt' => '',
                                        'product_code/cr' => '1',
                                        'gl/sl acct no' => $formattedAccountNumber,
                                        'amount' => number_format($remainingAmount, 2, '.', '')
                                    ]);
                                } else {
                                    Log::warning("No Savings Deposit-Regular account found for member {$member->id}");
                                }
                            }
                        } else {
                            Log::warning("No savings account found for member {$member->id} with product name '{$productName}'");
                        }
                    }
                }

                // Add regular savings based on account number pattern
                // Check for account numbers with third segment indicating regular savings (e.g., 20101)
                foreach ($member->savings as $savings) {
                    $accountNumber = $savings->account_number;
                    $segments = explode('-', $accountNumber);

                    Log::info("Checking savings account: {$accountNumber}, segments: " . json_encode($segments));

                    // Check if third segment (index 2) indicates regular savings
                    if (count($segments) >= 3 && $segments[2] === '20101') {
                        // This is a regular savings account based on account number pattern
                        $remittanceAmount = $savings->remittance_amount ?? 0;
                        $deductionAmount = $savings->deduction_amount ?? 0;

                        // Apply deduction: remaining amount = remittance - deduction
                        $remainingAmount = $remittanceAmount - $deductionAmount;

                        if ($remainingAmount > 0) {
                            $originalAccountNumber = $savings->account_number;
                            $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);

                            Log::info("Account number transformation details:");
                            Log::info("  Original: '{$originalAccountNumber}' (type: " . gettype($originalAccountNumber) . ", length: " . strlen($originalAccountNumber) . ")");
                            Log::info("  After preg_replace: '{$formattedAccountNumber}' (type: " . gettype($formattedAccountNumber) . ", length: " . strlen($formattedAccountNumber) . ")");
                            Log::info("  Original segments: " . json_encode(explode('-', $originalAccountNumber)));
                            Log::info("  Expected result: " . implode('', explode('-', $originalAccountNumber)));

                            $exportRows->push([
                                'branch_code' => $member->branch->code ?? '',
                                'product_code/dr' => '',
                                'gl/sl cct no' => '',
                                'amt' => '',
                                'product_code/cr' => '1',
                                'gl/sl acct no' => $formattedAccountNumber,
                                'amount' => number_format($remainingAmount, 2, '.', '')
                            ]);

                            Log::info("Added regular savings export row for member {$member->id}, account: {$originalAccountNumber}, remittance: {$remittanceAmount}, deduction: {$deductionAmount}, remaining: {$remainingAmount}");
                        }
                    }
                }
            }
        }

        return $exportRows;
    }
}
