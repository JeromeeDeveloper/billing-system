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

            $remitted = $record['share_amount'];

            // 1. Mortuary rows: all savings with product_name 'Mortuary' and deduction_amount > 0
            $totalMortuaryDeduction = 0;
            foreach ($member->savings as $saving) {
                if (
                    $saving->savingProduct &&
                    strtolower($saving->savingProduct->product_name) === 'mortuary' &&
                    ($saving->deduction_amount ?? 0) > 0
                ) {
                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => '1',
                        'gl/sl acct no' => str_replace('-', '', $saving->account_number),
                        'amount' => number_format($saving->deduction_amount, 2, '.', '')
                    ]);
                    $remitted -= $saving->deduction_amount;
                    $totalMortuaryDeduction += $saving->deduction_amount;
                }
            }

            $shareSaving = $member->savings->first(function ($s) {
                return str_contains(strtolower($s->savingProduct->product_name), 'share');
            });
            $regularSaving = $member->savings->first(function ($s) {
                return str_contains(strtolower($s->savingProduct->product_name), 'Savings Deposit-Regular');
            });
            $shareAccount = $member->shares->first();

            // Calculate the total deduction amount from all of the member's share accounts
            $shareDeduction = $member->shares->sum('deduction_amount');

            // 2. Share row
            if ($shareAccount) {
                // The amount for this row is the total of all share deduction_amounts
                if ($shareDeduction > 0 && $remitted >= $shareDeduction) {
                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => '2',
                        'gl/sl acct no' => str_replace('-', '', $shareAccount->account_number),
                        'amount' => number_format($shareDeduction, 2, '.', '')
                    ]);
                    $remitted -= $shareDeduction;
                }
            }

            // 3. Remaining to Regular Savings
            if ($regularSaving && $remitted > 0) {
                $exportRows->push([
                    'branch_code' => $member->branch->code ?? '',
                    'product_code/dr' => '',
                    'gl/sl cct no' => '',
                    'amt' => '',
                    'product_code/cr' => '1',
                    'gl/sl acct no' => str_replace('-', '', $regularSaving->account_number),
                    'amount' => number_format($remitted, 2, '.', '')
                ]);
            }
        }

        return $exportRows;
    }
}
