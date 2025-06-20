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
        $shareDeduction = 50.00; // As per example, assuming fixed.

        foreach ($this->remittanceData as $record) {
            if (empty($record['member_id']) || $record['share_amount'] <= 0) {
                continue;
            }

            $member = Member::with(['branch', 'savings.savingProduct', 'shares'])->find($record['member_id']);

            if (!$member) {
                Log::warning('Member not found for record: ' . json_encode($record));
                continue;
            }

            $totalRemitted = $record['share_amount'];
            $amountToDistribute = $totalRemitted;

            $mortuarySaving = $member->savings->first(function ($s) {
                return str_contains(strtolower($s->savingProduct->product_name), 'mortuary');
            });

            $regularSaving = $member->savings->first(function ($s) {
                return str_contains(strtolower($s->savingProduct->product_name), 'regular');
            });

            $shareAccount = $member->shares->first();

            $mortuaryDeduction = 0;
            if ($mortuarySaving) {
                $mortuaryDeduction = $mortuarySaving->deduction_amount ?? 0;
                if ($amountToDistribute >= $mortuaryDeduction) {
                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => $mortuarySaving->savingProduct->product_code,
                        'gl/sl acct no' => str_replace('-', '', $mortuarySaving->account_number),
                        'amount' => number_format($mortuaryDeduction, 2, '.', '')
                    ]);
                    $amountToDistribute -= $mortuaryDeduction;
                }
            }

            if ($shareAccount && $amountToDistribute >= $shareDeduction) {
                $exportRows->push([
                    'branch_code' => $member->branch->code ?? '',
                    'product_code/dr' => '',
                    'gl/sl cct no' => '',
                    'amt' => '',
                    'product_code/cr' => '2', // Assuming '2' is for Share Capital
                    'gl/sl acct no' => str_replace('-', '', $shareAccount->account_number),
                    'amount' => number_format($shareDeduction, 2, '.', '')
                ]);
                $amountToDistribute -= $shareDeduction;
            }

            if ($regularSaving && $amountToDistribute > 0) {
                $exportRows->push([
                    'branch_code' => $member->branch->code ?? '',
                    'product_code/dr' => '',
                    'gl/sl cct no' => '',
                    'amt' => '',
                    'product_code/cr' => '1',
                    'gl/sl acct no' => str_replace('-', '', $regularSaving->account_number),
                    'amount' => number_format($amountToDistribute, 2, '.', '')
                ]);
            }
        }

        return $exportRows;
    }
}
