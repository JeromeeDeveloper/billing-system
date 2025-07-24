<div class="container-fluid">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            {{ $type }} Billing Remittance
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Remitted Loans</th>
                            <th>Remitted Savings</th>
                            <th>Remitted Shares</th>
                            <th>Total Remitted</th>
                            <th>Total Billed</th>
                            <th>Remaining Loans</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalLoans = 0;
                            $totalSavings = 0;
                            $totalShares = 0;
                            $totalRemitted = 0;
                            $totalBilled = 0;
                            $totalRemaining = 0;
                            $billingType = strtolower($type);
                            $billingPeriod = auth()->user()->billing_period;
                            $billedTotals = [];
                        @endphp
                        @foreach($remittances as $remit)
                            @php
                                $member = $remit->member;
                                $remittedLoans = $remit->remitted_amount ?? 0;
                                $remittedSavings = $remit->remitted_savings ?? 0;
                                $remittedShares = $remit->remitted_shares ?? 0;
                                $totalRemit = $remittedLoans + $remittedSavings + $remittedShares;
                                // Compute billed total for this member and billing type
                                $billedTotal = \App\Models\LoanForecast::where('member_id', $remit->member_id)
                                    ->where('billing_period', $billingPeriod)
                                    ->get()
                                    ->filter(function($forecast) use ($billingType) {
                                        $productCode = null;
                                        if ($forecast->loan_acct_no) {
                                            $segments = explode('-', $forecast->loan_acct_no);
                                            $productCode = $segments[2] ?? null;
                                        }
                                        $product = $productCode ? \App\Models\LoanProduct::where('product_code', $productCode)->first() : null;
                                        return $product && $product->billing_type === $billingType;
                                    })
                                    ->sum('total_due');
                                $remainingLoans = $billedTotal - $remittedLoans;
                                $totalLoans += $remittedLoans;
                                $totalSavings += $remittedSavings;
                                $totalShares += $remittedShares;
                                $totalRemitted += $totalRemit;
                                $totalBilled += $billedTotal;
                                $totalRemaining += $remainingLoans;
                                $billedTotals[] = $billedTotal;
                            @endphp
                            <tr>
                                <td>{{ $member->full_name ?? ($member->fname . ' ' . $member->lname) ?? 'N/A' }}</td>
                                <td>{{ number_format($remittedLoans, 2) }}</td>
                                <td>{{ number_format($remittedSavings, 2) }}</td>
                                <td>{{ number_format($remittedShares, 2) }}</td>
                                <td>{{ number_format($totalRemit, 2) }}</td>
                                <td>{{ number_format($billedTotal, 2) }}</td>
                                <td>{{ number_format($remainingLoans, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total</th>
                            <th>{{ number_format($totalLoans, 2) }}</th>
                            <th>{{ number_format($totalSavings, 2) }}</th>
                            <th>{{ number_format($totalShares, 2) }}</th>
                            <th>{{ number_format($totalRemitted, 2) }}</th>
                            <th>{{ number_format(array_sum($billedTotals), 2) }}</th>
                            <th>{{ number_format($totalRemaining, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
