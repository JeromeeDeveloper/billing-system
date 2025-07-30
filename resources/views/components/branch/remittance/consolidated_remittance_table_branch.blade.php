<div class="container-fluid">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Branch Consolidated Remittance Report</h5>
            <div class="d-flex gap-2 flex-wrap">
                <select id="billingTypeFilter" class="form-select form-select-sm" style="width: auto;">
                    <option value="">All Types</option>
                    <option value="regular">Regular Billing</option>
                    <option value="special">Special Billing</option>
                </select>
                <select id="statusFilter" class="form-select form-select-sm" style="width: auto;">
                    <option value="">All Status</option>
                    <option value="success">Matched</option>
                    <option value="danger">Unmatched</option>
                    <option value="no_branch">No Branch</option>
                </select>
                <input type="text" id="searchFilter" class="form-control form-control-sm" placeholder="Search member..." style="width: 200px;">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center" id="consolidatedTable">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Type</th>
                            <th>Remitted Loans</th>
                            <th>Remitted Savings</th>
                            <th>Remitted Shares</th>
                            <th>Total Remitted</th>
                            <th>Total Billed</th>
                            <th>Remaining Balance</th>
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
                            $billingPeriod = auth()->user()->billing_period;
                            $branch_id = auth()->user()->branch_id;

                            // Collect all data by member ID/CID (branch members only)
                            $consolidatedData = [];

                            // Process Regular Billing data (branch members only)
                            if(isset($regularRemittances) && $regularRemittances->count() > 0) {
                                foreach($regularRemittances as $remit) {
                                    // Check if member belongs to this branch
                                    $member = $remit->member;
                                    if (!$member || $member->branch_id != $branch_id) {
                                        continue;
                                    }

                                    $memberId = $remit->member_id;
                                    $memberName = $member->full_name ?? 'N/A';

                                                                        // Get billed total for this member based on loan_acct_no and billing type
                                    // For branch, use the LoanRemittance's loanForecast relationship
                                    $billedTotal = 0;
                                    if ($remit->loanForecast) {
                                        $forecast = $remit->loanForecast;
                                        $productCode = null;
                                        if ($forecast->loan_acct_no) {
                                            $segments = explode('-', $forecast->loan_acct_no);
                                            $productCode = $segments[2] ?? null;
                                        }
                                        $product = $productCode ? \App\Models\LoanProduct::where('product_code', $productCode)->first() : null;
                                        if ($product && $product->billing_type === 'regular') {
                                            $billedTotal = $forecast->total_due;
                                        }
                                    }


                                    if ($remit->loanForecast) {
                                        $forecast = $remit->loanForecast;
                                        $productCode = null;
                                        if ($forecast->loan_acct_no) {
                                            $segments = explode('-', $forecast->loan_acct_no);
                                            $productCode = $segments[2] ?? null;
                                        }
                                        $product = $productCode ? \App\Models\LoanProduct::where('product_code', $productCode)->first() : null;

                                    }

                                                        $consolidatedData[$memberId] = [
                        'member_id' => $memberId,
                        'member_name' => $memberName,
                        'billing_type' => 'regular',
                        'remitted_loans' => $remit->remitted_amount ?? 0,
                        'remitted_savings' => 0, // LoanRemittance doesn't have savings field
                        'remitted_shares' => 0,  // LoanRemittance doesn't have shares field
                        'total_remitted' => $remit->remitted_amount ?? 0,
                        'total_billed' => $billedTotal,
                        'remaining_balance' => $billedTotal - ($remit->remitted_amount ?? 0),
                        'status_class' => ($billedTotal - ($remit->remitted_amount ?? 0)) <= 0 ? 'success' : 'warning'
                    ];
                                }
                            }

                            // Process Special Billing data (branch members only)
                            if(isset($specialRemittances) && $specialRemittances->count() > 0) {
                                foreach($specialRemittances as $remit) {
                                    // Check if member belongs to this branch
                                    $member = $remit->member;
                                    if (!$member || $member->branch_id != $branch_id) {
                                        continue;
                                    }

                                    $memberId = $remit->member_id;
                                    $memberName = $member->full_name ?? 'N/A';

                                                                        // Get billed total for this member based on loan_acct_no and billing type
                                    // For branch, use the LoanRemittance's loanForecast relationship
                                    $billedTotal = 0;
                                    if ($remit->loanForecast) {
                                        $forecast = $remit->loanForecast;
                                        $productCode = null;
                                        if ($forecast->loan_acct_no) {
                                            $segments = explode('-', $forecast->loan_acct_no);
                                            $productCode = $segments[2] ?? null;
                                        }
                                        $product = $productCode ? \App\Models\LoanProduct::where('product_code', $productCode)->first() : null;
                                        if ($product && $product->billing_type === 'special') {
                                            $billedTotal = $forecast->total_due;
                                        }
                                    }

                                    // Debug: Log the calculation details for branch special billing
                                    $debugDetails = [];
                                    if ($remit->loanForecast) {
                                        $forecast = $remit->loanForecast;
                                        $productCode = null;
                                        if ($forecast->loan_acct_no) {
                                            $segments = explode('-', $forecast->loan_acct_no);
                                            $productCode = $segments[2] ?? null;
                                        }
                                        $product = $productCode ? \App\Models\LoanProduct::where('product_code', $productCode)->first() : null;
                                        $debugDetails[] = [
                                            'loan_acct_no' => $forecast->loan_acct_no,
                                            'product_code' => $productCode,
                                            'product_exists' => $product ? 'YES' : 'NO',
                                            'billing_type' => $product ? $product->billing_type : 'N/A',
                                            'total_due' => $forecast->total_due,
                                            'included' => ($product && $product->billing_type === 'special') ? 'YES' : 'NO'
                                        ];
                                    }

                                                        $consolidatedData[$memberId] = [
                        'member_id' => $memberId,
                        'member_name' => $memberName,
                        'billing_type' => 'special',
                        'remitted_loans' => $remit->remitted_amount ?? 0,
                        'remitted_savings' => 0, // LoanRemittance doesn't have savings field
                        'remitted_shares' => 0,  // LoanRemittance doesn't have shares field
                        'total_remitted' => $remit->remitted_amount ?? 0,
                        'total_billed' => $billedTotal,
                        'remaining_balance' => $billedTotal - ($remit->remitted_amount ?? 0),
                        'status_class' => ($billedTotal - ($remit->remitted_amount ?? 0)) <= 0 ? 'success' : 'warning'
                    ];
                                }
                            }

                            // Process Upload Preview data and merge with existing records (branch members only)
                            if(isset($loansSavingsPreviewPaginated) && $loansSavingsPreviewPaginated->count() > 0) {
                                foreach($loansSavingsPreviewPaginated as $row) {
                                    $memberId = $row['member_id'] ?? null;
                                    if (!$memberId) continue;

                                    // Check if member belongs to this branch
                                    $member = \App\Models\Member::find($memberId);
                                    if (!$member || $member->branch_id != $branch_id) {
                                        continue;
                                    }

                                    $statusClass = $row['status'] === 'success' ? 'success' : 'danger';
                                    $isNoBranch = isset($row['message']) && str_contains(strtolower($row['message']), 'no branch');
                                    if ($isNoBranch) {
                                        $statusClass = 'no_branch';
                                    }

                                    if (isset($consolidatedData[$memberId])) {
                                        // Merge with existing billing data
                                        $consolidatedData[$memberId]['status_class'] = $statusClass;
                                    } else {
                                        // Create new record for preview only
                                        $consolidatedData[$memberId] = [
                                            'member_id' => $memberId,
                                            'member_name' => $row['name'] ?? 'N/A',
                                            'billing_type' => 'preview',
                                            'remitted_loans' => $row['loans'] ?? 0,
                                            'remitted_savings' => $row['savings'] ?? 0,
                                            'remitted_shares' => 0,
                                            'total_remitted' => ($row['loans'] ?? 0) + ($row['savings'] ?? 0),
                                            'total_billed' => 0,
                                            'remaining_balance' => 0,
                                            'status_class' => $statusClass
                                        ];
                                    }
                                }
                            }

                            // Process Shares Preview data and merge with existing records (branch members only)
                            if(isset($sharesPreviewPaginated) && $sharesPreviewPaginated->count() > 0) {
                                foreach($sharesPreviewPaginated as $row) {
                                    $memberId = $row['member_id'] ?? null;
                                    if (!$memberId) continue;

                                    // Check if member belongs to this branch
                                    $member = \App\Models\Member::find($memberId);
                                    if (!$member || $member->branch_id != $branch_id) {
                                        continue;
                                    }

                                    $statusClass = $row['status'] === 'success' ? 'success' : 'danger';
                                    $isNoBranch = isset($row['message']) && str_contains(strtolower($row['message']), 'no branch');
                                    if ($isNoBranch) {
                                        $statusClass = 'no_branch';
                                    }

                                    if (isset($consolidatedData[$memberId])) {
                                        // Merge with existing data
                                        $consolidatedData[$memberId]['remitted_shares'] += $row['share_amount'] ?? 0;
                                        $consolidatedData[$memberId]['total_remitted'] += $row['share_amount'] ?? 0;
                                        $consolidatedData[$memberId]['status_class'] = $statusClass;
                                    } else {
                                        // Create new record for shares preview only
                                        $consolidatedData[$memberId] = [
                                            'member_id' => $memberId,
                                            'member_name' => $row['name'] ?? 'N/A',
                                            'billing_type' => 'preview',
                                            'remitted_loans' => 0,
                                            'remitted_savings' => 0,
                                            'remitted_shares' => $row['share_amount'] ?? 0,
                                            'total_remitted' => $row['share_amount'] ?? 0,
                                            'total_billed' => 0,
                                            'remaining_balance' => 0,
                                            'status_class' => $statusClass
                                        ];
                                    }
                                }
                            }
                        @endphp

                        {{-- No Data Message --}}
                        @if(empty($consolidatedData))
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    No consolidated data available. Please check if data is being passed correctly.
                                </td>
                            </tr>
                        @endif

                        {{-- Display Consolidated Data (Branch Members Only) --}}
                        @foreach($consolidatedData as $memberId => $data)
                            @php
                                // Skip upload preview only records when showing all types
                                if ($data['billing_type'] === 'preview') {
                                    continue;
                                }

                                $totalLoans += $data['remitted_loans'];
                                $totalSavings += $data['remitted_savings'];
                                $totalShares += $data['remitted_shares'];
                                $totalRemitted += $data['total_remitted'];
                                $totalBilled += $data['total_billed'];
                                $totalRemaining += $data['remaining_balance'];

                                $billingTypeLabel = $data['billing_type'] === 'regular' ? 'Regular Billing' : 'Special Billing';
                                $billingTypeClass = $data['billing_type'] === 'regular' ? 'primary' : 'warning';
                            @endphp
                            <tr class="data-row" data-billing-type="{{ $data['billing_type'] }}" data-status="{{ $data['status_class'] }}">
                                <td>{{ $data['member_name'] }}</td>
                                <td><span class="badge badge-{{ $billingTypeClass }}">{{ $billingTypeLabel }}</span></td>
                                <td>{{ number_format($data['remitted_loans'], 2) }}</td>
                                <td>{{ number_format($data['remitted_savings'], 2) }}</td>
                                <td>{{ number_format($data['remitted_shares'], 2) }}</td>
                                <td>{{ number_format($data['total_remitted'], 2) }}</td>
                                                    <td>
                        {{ $data['total_billed'] > 0 ? number_format($data['total_billed'], 2) : '-' }}
                    </td>
                                <td class="{{ $data['remaining_balance'] < 0 ? 'text-success' : ($data['remaining_balance'] > 0 ? 'text-danger' : 'text-muted') }}">
                                    {{ $data['remaining_balance'] != 0 ? number_format($data['remaining_balance'], 2) : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-info">
                            <th colspan="2">Total</th>
                            <th>{{ number_format($totalLoans, 2) }}</th>
                            <th>{{ number_format($totalSavings, 2) }}</th>
                            <th>{{ number_format($totalShares, 2) }}</th>
                            <th>{{ number_format($totalRemitted, 2) }}</th>
                            <th>{{ number_format($totalBilled, 2) }}</th>
                            <th class="{{ $totalRemaining < 0 ? 'text-success' : ($totalRemaining > 0 ? 'text-danger' : 'text-muted') }}">
                                {{ number_format($totalRemaining, 2) }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const billingTypeFilter = document.getElementById('billingTypeFilter');
    const statusFilter = document.getElementById('statusFilter');
    const searchFilter = document.getElementById('searchFilter');
    const tableRows = document.querySelectorAll('.data-row');

    function filterTable() {
        const billingType = billingTypeFilter.value;
        const status = statusFilter.value;
        const searchTerm = searchFilter.value.toLowerCase();

        tableRows.forEach(row => {
            const rowBillingType = row.getAttribute('data-billing-type');
            const rowStatus = row.getAttribute('data-status');
            const memberName = row.cells[0].textContent.toLowerCase();

            let showRow = true;

            // Filter by billing type
            if (billingType && rowBillingType !== billingType) {
                showRow = false;
            }

            // Filter by status
            if (status) {
                if (status === 'success' && rowStatus !== 'success') {
                    showRow = false;
                } else if (status === 'danger' && rowStatus !== 'danger') {
                    showRow = false;
                } else if (status === 'no_branch' && rowStatus !== 'no_branch') {
                    showRow = false;
                }
            }

            // Filter by search term
            if (searchTerm && !memberName.includes(searchTerm)) {
                showRow = false;
            }

            row.style.display = showRow ? '' : 'none';
        });
    }

    billingTypeFilter.addEventListener('change', filterTable);
    statusFilter.addEventListener('change', filterTable);
    searchFilter.addEventListener('input', filterTable);
});
</script>
