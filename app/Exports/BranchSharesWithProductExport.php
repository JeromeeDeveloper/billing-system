<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\ShareProduct;
use App\Models\SavingProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BranchSharesWithProductExport implements FromCollection, WithHeadings
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
            $member = Member::with(['branch', 'savings.savingProduct', 'shares.shareProduct'])
                ->where('branch_id', $this->branch_id)
                ->find($record['member_id']);
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

    private function getSavingProductName($savingAccount)
    {
        if ($savingAccount->savingProduct && $savingAccount->savingProduct->name) {
            return $savingAccount->savingProduct->name;
        }
        if ($savingAccount->product_name) {
            return $savingAccount->product_name;
        }
        if ($savingAccount->product_code) {
            $savingProduct = SavingProduct::where('product_code', $savingAccount->product_code)->first();
            if ($savingProduct && $savingProduct->name) {
                return $savingProduct->name;
            }
        }
        if ($savingAccount->product_code) {
            return "Savings Product {$savingAccount->product_code}";
        }
        return 'Unknown Savings Product';
    }

    private function getShareProductName($shareAccount)
    {
        if ($shareAccount->shareProduct && $shareAccount->shareProduct->name) {
            return $shareAccount->shareProduct->name;
        }
        if ($shareAccount->product_name) {
            return $shareAccount->product_name;
        }
        if ($shareAccount->product_code) {
            $shareProduct = ShareProduct::where('product_code', $shareAccount->product_code)->first();
            if ($shareProduct && $shareProduct->name) {
                return $shareProduct->name;
            }
        }
        if ($shareAccount->product_code) {
            return "Share Product {$shareAccount->product_code}";
        }
        return 'Unknown Share Product';
    }
}
