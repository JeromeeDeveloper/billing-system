<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\SavingProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Log;

class SharesExport implements FromCollection, WithHeadings
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
            'amount'
        ];
    }

    public function collection()
    {
        $exportRows = new Collection();

        foreach ($this->remittanceData as $record) {
            if (empty($record['member_id']) || $record['share_amount'] <= 0) {
                continue;
            }

            $member = Member::with(['branch', 'savings.savingProduct', 'shares'])->find($record['member_id']);
            if (!$member) continue;

            // Skip members with no branch
            if (empty($member->branch) || empty($member->branch->code)) {
                continue;
            }

            $remitted = $record['share_amount'];

            // 1. Mortuary rows: all savings with product_type 'mortuary' and deduction_amount > 0
            $totalMortuaryDeduction = 0;
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
                        'amount' => number_format($saving->deduction_amount, 2, '.', '')
                    ]);
                    $remitted -= $saving->deduction_amount;
                    $totalMortuaryDeduction += $saving->deduction_amount;
                }
            }

            $shareSaving = $member->savings->first(function ($s) {
                return $s->savingProduct && $s->savingProduct->product_type === 'share';
            });
            $regularSaving = $member->savings->first(function ($s) {
                return $s->savingProduct && $s->savingProduct->product_type === 'regular';
            });
            $shareAccount = $member->shares->first();

            // Calculate the total deduction amount from all of the member's share accounts
            $shareDeduction = $member->shares->sum('deduction_amount');

            // 2. Share row
            if ($shareAccount) {
                // The amount for this row is the total of all share deduction_amounts
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
                        'amount' => number_format($shareDeduction, 2, '.', '')
                    ]);
                    $remitted -= $shareDeduction;
                }
            }

            // 3. Remaining to Regular Savings
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
                    'amount' => number_format($remitted, 2, '.', '')
                ]);
            }
        }

        return $exportRows;
    }
}

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
        $exportRows = new \Illuminate\Support\Collection();
        foreach ($this->remittanceData as $record) {
            if (empty($record['member_id']) || $record['share_amount'] <= 0) {
                continue;
            }
            $member = \App\Models\Member::with(['branch', 'savings.savingProduct', 'shares'])->find($record['member_id']);
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
                        'product_name' => $saving->savingProduct->name ?? '',
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
                        'product_name' => $shareAccount->shareProduct->name ?? '',
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
                    'product_name' => $regularSaving->savingProduct->name ?? '',
                ]);
            }
        }
        return $exportRows;
    }
}
