<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\SavingProduct;
use App\Models\ContraAcc;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Log;

class BranchSharesExport implements FromCollection, WithHeadings
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
        $branchTotals = [
            'shares' => 0,
            'savings' => 0
        ];

        foreach ($this->remittanceData as $record) {
            if (empty($record->member_id) || $record->share_amount <= 0) {
                continue;
            }

            $member = Member::with(['branch', 'savings.savingProduct', 'shares'])
                ->where('branch_id', $this->branch_id) // Only get members from this branch
                ->find($record->member_id);

            if (!$member) {
                Log::warning('Member not found or not in branch for record: ' . json_encode($record));
                continue;
            }

            // Skip members with no branch
            if (empty($member->branch) || empty($member->branch->code)) {
                continue;
            }

            // Verify member belongs to the correct branch
            if ($member->branch_id !== $this->branch_id) {
                Log::warning('Member ' . $member->id . ' does not belong to branch ' . $this->branch_id);
                continue;
            }

            $remitted = $record->share_amount;

            // 1. Mortuary rows: all savings with product_type 'mortuary' and deduction_amount > 0
            $totalMortuaryDeduction = 0;
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
                        'gl/sl acct no' => "'" . str_replace('-', '', $saving->account_number),
                        'amount' => number_format($saving->deduction_amount, 2, '.', '')
                    ]);
                    $remitted -= $saving->deduction_amount;
                    $totalMortuaryDeduction += $saving->deduction_amount;
                    $branchTotals['savings'] += $saving->deduction_amount;
                }
            }

            $shareSaving = $member->savings->first(function ($s) {
                return str_contains(strtolower($s->savingProduct->product_name), 'share');
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
                    $exportRows->push([
                        'branch_code' => $member->branch->code ?? '',
                        'product_code/dr' => '',
                        'gl/sl cct no' => '',
                        'amt' => '',
                        'product_code/cr' => '2',
                        'gl/sl acct no' => "'" . str_replace('-', '', $shareAccount->account_number),
                        'amount' => number_format($shareDeduction, 2, '.', '')
                    ]);
                    $remitted -= $shareDeduction;
                    $branchTotals['shares'] += $shareDeduction;
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
                    'gl/sl acct no' => "'" . str_replace('-', '', $regularSaving->account_number),
                    'amount' => number_format($remitted, 2, '.', '')
                ]);
                $branchTotals['savings'] += $remitted;
            }
        }

        // Get branch info for summary rows
        $branch = Member::where('branch_id', $this->branch_id)->with('branch')->first();
        $branchCode = $branch->branch->code ?? '';

        // Add branch totals summary rows
        if ($branchTotals['shares'] > 0) {
            // Get contra account for shares
            $sharesContra = ContraAcc::where('type', 'shares')->first();
            if ($sharesContra) {
                $exportRows->push([
                    'branch_code' => $branchCode,
                    'product_code/dr' => '2',
                    'gl/sl cct no' => $sharesContra->account_number,
                    'amt' => number_format($branchTotals['shares'], 2, '.', ''),
                    'product_code/cr' => '',
                    'gl/sl acct no' => '',
                    'amount' => ''
                ]);
            }
        }

        if ($branchTotals['savings'] > 0) {
            // Get contra account for savings
            $savingsContra = ContraAcc::where('type', 'savings')->first();
            if ($savingsContra) {
                $exportRows->push([
                    'branch_code' => $branchCode,
                    'product_code/dr' => '1',
                    'gl/sl cct no' => $savingsContra->account_number,
                    'amt' => number_format($branchTotals['savings'], 2, '.', ''),
                    'product_code/cr' => '',
                    'gl/sl acct no' => '',
                    'amount' => ''
                ]);
            }
        }

        return $exportRows;
    }
}
