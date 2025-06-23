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

            // 2. Process shares with deduction_amount > 0
            foreach ($member->shares as $share) {
                if (($share->deduction_amount ?? 0) > 0) {
                    Log::info('Processing shares deduction for member: ' . $member->id . ', account: ' . $share->account_number);
                    Log::info('Share deduction amount from database: [' . $share->deduction_amount . ']');

                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => '2',
                        'gl/sl acct no' => str_replace('-', '', $share->account_number),
                        'amount' => number_format($share->deduction_amount, 2, '.', '')
                    ]);
                    $remitted -= $share->deduction_amount;
                }
            }

            $shareSaving = $member->savings->first(function ($s) {
                return str_contains(strtolower($s->savingProduct->product_name), 'share');
            });
            $regularSaving = $member->savings->first(function ($s) {
                return str_contains(strtolower($s->savingProduct->product_name), 'regular');
            });
            $shareAccount = $member->shares->first();

            $shareDeduction = $shareSaving ? ($shareSaving->deduction_amount ?? 0) : 0;

            // 3. Share row (mortuary deduction + share deduction)
            $shareRowAmount = 0;
            if ($shareAccount) {
                $shareRowAmount = $totalMortuaryDeduction + $shareDeduction;
                if ($shareRowAmount > 0 && $remitted >= $shareRowAmount) {
                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => '2', // or $shareAccount->savingProduct->product_code if you have it
                        'gl/sl acct no' => str_replace('-', '', $shareAccount->account_number),
                        'amount' => number_format($shareRowAmount, 2, '.', '')
                    ]);
                    $remitted -= $shareRowAmount;
                }
            }

            // 4. Remaining to Regular Savings
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
