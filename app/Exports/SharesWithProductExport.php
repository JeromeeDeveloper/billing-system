<?php

namespace App\Exports;

use App\Models\Member;
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
            $member = Member::with(['branch', 'savings.savingProduct', 'shares'])->find($record['member_id']);
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
                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => '1',
                        'gl/sl acct no' => str_replace('-', '', $saving->account_number),
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
                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => '2',
                        'gl/sl acct no' => str_replace('-', '', $shareAccount->account_number),
                        'amount' => number_format($shareDeduction, 2, '.', ''),
                        'product_name' => $shareAccount->shareProduct->name ?? '',
                    ]);
                    $remitted -= $shareDeduction;
                }
            }
            // Remaining to Regular Savings
            if ($regularSaving && $remitted > 0) {
                $exportRows->push([
                    'branch_code' => $member->branch->code ?? '',
                    'product_code/dr' => '',
                    'gl/sl cct no' => '',
                    'amt' => '',
                    'product_code/cr' => '1',
                    'gl/sl acct no' => str_replace('-', '', $regularSaving->account_number),
                    'amount' => number_format($remitted, 2, '.', ''),
                    'product_name' => $regularSaving->savingProduct->name ?? '',
                ]);
            }
        }
        return $exportRows;
    }
}
