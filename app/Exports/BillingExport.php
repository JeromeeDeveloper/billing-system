<?php

namespace App\Exports;

use App\Models\Member;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class BillingExport implements FromCollection, WithHeadings
{
    protected $billingPeriod;

    public function __construct()
    {
        $this->billingPeriod = Auth::user()->billing_period;
    }

    public function collection()
    {
        $members = Member::where(function ($query) {
            $query->where('account_status', 'deduction')
                ->orWhere(function ($query) {
                    $query->where('account_status', 'non-deduction')
                        ->where(function ($q) {
                            $q->whereDate('start_hold', '>', now()->toDateString())  // today before start_hold
                                ->orWhereDate('expiry_date', '<=', now()->toDateString()); // OR expiry_date reached/passed
                        });
                });
        })
            ->whereHas('loanForecasts', function ($query) {
                $query->where('billing_period', $this->billingPeriod);
            })
            ->with(['loanForecasts' => function ($query) {
                $query->where('billing_period', $this->billingPeriod);
            }])
            ->get();



        return $members->map(function ($member) {
            $forecast = $member->loanForecasts->first();

            return [
                'emp_id'        => $member->emp_id ?? 'N/A',
                'amortization'  => $member->loan_balance ?? 0,
                'name'          => "{$member->fname} {$member->lname}",
                'start_date'    => $member->start_date ? $member->start_date->format('Y-m-d') : 'N/A',
                'end_date'      => $member->end_date ? $member->end_date->format('Y-m-d') : 'N/A',
                'gross'         => $member->principal ?? 0,
                'office'        => $member->area_officer ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee #',
            'Amortization',
            'Name',
            'Start Date',
            'End Date',
            'Gross',
            'Office',
        ];
    }
}
