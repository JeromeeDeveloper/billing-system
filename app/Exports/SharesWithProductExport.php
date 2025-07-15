<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\ShareProduct;
use App\Models\SavingProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SharesWithProductExport implements FromCollection, WithHeadings
{
    protected $remittanceData;

    public function __construct($remittanceData)
    {
        $this->remittanceData = $remittanceData;
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
            'amount',
            'product_name'
        ];
    }

    public function collection()
    {
        $exportRows = new Collection();
        foreach ($this->remittanceData as $record) {
            if (empty($record['member_id']) || $record['share_amount'] <= 0) {
                continue;
            }
            $member = Member::with(['branch', 'savings.savingProduct', 'shares.shareProduct'])->find($record['member_id']);
            if (!$member) continue;
            if (empty($member->branch) || empty($member->branch->code)) {
                continue;
            }
            $remitted = $record['share_amount'];

            // Mortuary rows
            foreach ($member->savings as $saving) {
                if (
                    $saving->savingProduct &&
                    $saving->savingProduct->product_type === 'mortuary' &&
                    ($saving->deduction_amount ?? 0) > 0
                ) {
                    $originalAccountNumber = $saving->account_number;
                    $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);

                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => '1',
                        'gl/sl acct no' => $formattedAccountNumber,
                        'amount' => number_format($saving->deduction_amount, 2, '.', ''),
                        'product_name' => $this->getSavingProductName($saving),
                    ]);
                    $remitted -= $saving->deduction_amount;
                }
            }

            $shareSaving = $member->savings->first(function ($s) {
                return $s->savingProduct && $s->savingProduct->product_type === 'share';
            });
            $regularSaving = $member->savings->first(function ($s) {
                return $s->savingProduct && $s->savingProduct->product_type === 'regular';
            });
            $shareAccount = $member->shares->first();
            $shareDeduction = $member->shares->sum('deduction_amount');

            // Share row
            if ($shareAccount) {
                if ($shareDeduction > 0 && $remitted >= $shareDeduction) {
                    $originalAccountNumber = $shareAccount->account_number;
                    $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);

                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => '2',
                        'gl/sl acct no' => $formattedAccountNumber,
                        'amount' => number_format($shareDeduction, 2, '.', ''),
                        'product_name' => $this->getShareProductName($shareAccount),
                    ]);
                    $remitted -= $shareDeduction;
                }
            }

            // Remaining to Regular Savings
            if ($regularSaving && $remitted > 0) {
                $originalAccountNumber = $regularSaving->account_number;
                $formattedAccountNumber = "'" . preg_replace('/-/', '', $originalAccountNumber);

                $exportRows->push([
                    'branch_code' => $member->branch->code ?? '',
                    'product_code/dr' => '',
                    'gl/sl cct no' => '',
                    'amt' => '',
                    'product_code/cr' => '1',
                    'gl/sl acct no' => $formattedAccountNumber,
                    'amount' => number_format($remitted, 2, '.', ''),
                    'product_name' => $this->getSavingProductName($regularSaving),
                ]);
            }
        }
        return $exportRows;
    }

    /**
     * Get saving product name with multiple fallback strategies
     */
    private function getSavingProductName($savingAccount)
    {
        // Strategy 1: Get from savingProduct relationship
        if ($savingAccount->savingProduct && $savingAccount->savingProduct->name) {
            return $savingAccount->savingProduct->name;
        }

        // Strategy 2: Get from product_name field in savings table
        if ($savingAccount->product_name) {
            return $savingAccount->product_name;
        }

        // Strategy 3: Query SavingProduct table directly using product_code
        if ($savingAccount->product_code) {
            $savingProduct = SavingProduct::where('product_code', $savingAccount->product_code)->first();
            if ($savingProduct && $savingProduct->name) {
                return $savingProduct->name;
            }
        }

        // Strategy 4: Generate a name from product code
        if ($savingAccount->product_code) {
            return "Savings Product {$savingAccount->product_code}";
        }

        return 'Unknown Savings Product';
    }

    /**
     * Get share product name with multiple fallback strategies
     */
    private function getShareProductName($shareAccount)
    {
        // Strategy 1: Get from shareProduct relationship
        if ($shareAccount->shareProduct && $shareAccount->shareProduct->name) {
            return $shareAccount->shareProduct->name;
        }

        // Strategy 2: Get from product_name field in shares table
        if ($shareAccount->product_name) {
            return $shareAccount->product_name;
        }

        // Strategy 3: Query ShareProduct table directly using product_code
        if ($shareAccount->product_code) {
            $shareProduct = ShareProduct::where('product_code', $shareAccount->product_code)->first();
            if ($shareProduct && $shareProduct->name) {
                return $shareProduct->name;
            }
        }

        // Strategy 4: Generate a name from product code
        if ($shareAccount->product_code) {
            return "Share Product {$shareAccount->product_code}";
        }

        return 'Unknown Share Product';
    }
}
