<div class="container-fluid">
    <div class="card mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center py-3">
            <div class="d-flex align-items-center">
                <i class="fa fa-chart-bar me-2"></i>
                <h5 class="mb-0 fw-bold">Consolidated Remittance Report</h5>
            </div>
            <div class="d-flex gap-3 align-items-center">
                <div class="d-flex gap-2">
                    <select id="billingTypeFilter" class="form-select form-select-sm border-0 bg-white bg-opacity-90" style="width: 140px;">
                        <option value="">All Types</option>
                        <option value="regular">Regular Billing</option>
                        <option value="special">Special Billing</option>
                    </select>
                    <select id="statusFilter" class="form-select form-select-sm" style="display: none;">
                        <option value="no_branch">No Branch</option>
                    </select>
                    <input type="text" id="searchFilter" class="form-control form-control-sm border-0 bg-white bg-opacity-90" placeholder="Search members..." style="width: 180px;">
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('remittance.exportRegularSpecial') }}" class="btn btn-light btn-sm shadow-sm">
                        <i class="fa fa-file-excel-o text-success me-1"></i> Export Regular & Special
                    </a>
                    <a href="{{ route('remittance.exportConsolidated') }}" class="btn btn-light btn-sm shadow-sm">
                        <i class="fa fa-file-excel-o text-info me-1"></i> Matched / Unmatched Remittance
                    </a>
                </div>
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
                            <th>Remaining Amort Due</th>
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

                            // Collect all data by member ID/CID
                            $consolidatedData = [];

                            // Process Regular Billing data
                            if(isset($regularRemittances) && $regularRemittances->count() > 0) {
                                foreach($regularRemittances as $remit) {
                                    // Handle both admin (object) and branch (model) data structures
                                    $memberId = is_object($remit) ? $remit->member_id : $remit['member_id'];
                                    $memberName = is_object($remit) ? ($remit->member->full_name ?? 'N/A') : ($remit['member']['full_name'] ?? 'N/A');
                                    $remittedAmount = is_object($remit) ? ($remit->remitted_amount ?? 0) : ($remit['remitted_amount'] ?? 0);
                                    $remittedSavings = is_object($remit) ? ($remit->remitted_savings ?? 0) : ($remit['remitted_savings'] ?? 0);
                                    $remittedShares = is_object($remit) ? ($remit->remitted_shares ?? 0) : ($remit['remitted_shares'] ?? 0);

                                    // Get billed total for this member based on loan_acct_no and billing type
                                    // Convert from CID to member_id for admin data
                                    $actualMemberId = $memberId;
                                    if (is_object($remit) && isset($remit->billing_type)) {
                                        // This is admin data, convert CID to member_id
                                        $member = \App\Models\Member::where('cid', $memberId)->first();
                                        $actualMemberId = $member ? $member->id : null;
                                    }

                                    $loanForecasts = \App\Models\LoanForecast::where('member_id', $actualMemberId)
                                        ->where('billing_period', $billingPeriod)
                                        ->get();

                                    $billedTotal = 0;
                                    foreach($loanForecasts as $forecast) {
                                        $productCode = null;
                                        if ($forecast->loan_acct_no) {
                                            $segments = explode('-', $forecast->loan_acct_no);
                                            $productCode = $segments[2] ?? null;
                                        }
                                        $product = $productCode ? \App\Models\LoanProduct::where('product_code', $productCode)->first() : null;
                                        if ($product && $product->billing_type === 'regular') {
                                            $billedTotal += $forecast->total_due;
                                        }
                                    }


                                    foreach($loanForecasts as $forecast) {
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
                        'remitted_loans' => $remittedAmount,
                        'remitted_savings' => $remittedSavings,
                        'remitted_shares' => $remittedShares,
                        'total_remitted' => $remittedAmount + $remittedSavings + $remittedShares,
                        'total_billed' => $billedTotal,
                        'remaining_balance' => $billedTotal - $remittedAmount,
                        'status_class' => ($billedTotal - $remittedAmount) <= 0 ? 'success' : 'warning'
                    ];
                                }
                            }

                            // Process Special Billing data
                            if(isset($specialRemittances) && $specialRemittances->count() > 0) {
                                foreach($specialRemittances as $remit) {
                                    // Handle both admin (object) and branch (model) data structures
                                    $memberId = is_object($remit) ? $remit->member_id : $remit['member_id'];
                                    $memberName = is_object($remit) ? ($remit->member->full_name ?? 'N/A') : ($remit['member']['full_name'] ?? 'N/A');
                                    $remittedAmount = is_object($remit) ? ($remit->remitted_amount ?? 0) : ($remit['remitted_amount'] ?? 0);
                                    $remittedSavings = is_object($remit) ? ($remit->remitted_savings ?? 0) : ($remit['remitted_savings'] ?? 0);
                                    $remittedShares = is_object($remit) ? ($remit->remitted_shares ?? 0) : ($remit['remitted_shares'] ?? 0);

                                    // Get billed total for this member based on loan_acct_no and billing type
                                    // Convert from CID to member_id for admin data
                                    $actualMemberId = $memberId;
                                    if (is_object($remit) && isset($remit->billing_type)) {
                                        // This is admin data, convert CID to member_id
                                        $member = \App\Models\Member::where('cid', $memberId)->first();
                                        $actualMemberId = $member ? $member->id : null;
                                    }

                                    $loanForecasts = \App\Models\LoanForecast::where('member_id', $actualMemberId)
                                        ->where('billing_period', $billingPeriod)
                                        ->get();

                                    $billedTotal = 0;
                                    foreach($loanForecasts as $forecast) {
                                        $productCode = null;
                                        if ($forecast->loan_acct_no) {
                                            $segments = explode('-', $forecast->loan_acct_no);
                                            $productCode = $segments[2] ?? null;
                                        }
                                        $product = $productCode ? \App\Models\LoanProduct::where('product_code', $productCode)->first() : null;
                                        if ($product && $product->billing_type === 'special') {
                                            $billedTotal += $forecast->total_due;
                                        }
                                    }

                                    // Debug: Log the calculation details for special billing
                                    $debugDetails = [];
                                    $debugDetails[] = [
                                        'original_member_id' => $memberId,
                                        'actual_member_id' => $actualMemberId,
                                        'member_found' => $actualMemberId ? 'YES' : 'NO',
                                        'loan_forecasts_count' => $loanForecasts->count()
                                    ];
                                    foreach($loanForecasts as $forecast) {
                                        $productCode = null;
                                        if ($forecast->loan_acct_no) {
                                            $segments = explode('-', $forecast->loan_acct_no);
                                            $productCode = $segments[2] ?? null;
                                        }
                                        $product = $productCode ? \App\Models\LoanProduct::where('product_code', $productCode)->first() : null;

                                        // Debug: Check if product exists and what billing_type it has
                                        $productExists = $product ? 'YES' : 'NO';
                                        $productBillingType = $product ? $product->billing_type : 'N/A';
                                        $allProducts = \App\Models\LoanProduct::where('product_code', $productCode)->get();
                                        $productCount = $allProducts->count();

                                        $debugDetails[] = [
                                            'loan_acct_no' => $forecast->loan_acct_no,
                                            'product_code' => $productCode,
                                            'product_exists' => $productExists,
                                            'product_count' => $productCount,
                                            'billing_type' => $productBillingType,
                                            'total_due' => $forecast->total_due,
                                            'included' => ($product && $product->billing_type === 'special') ? 'YES' : 'NO'
                                        ];
                                    }

                                                        $consolidatedData[$memberId] = [
                        'member_id' => $memberId,
                        'member_name' => $memberName,
                        'billing_type' => 'special',
                        'remitted_loans' => $remittedAmount,
                        'remitted_savings' => $remittedSavings,
                        'remitted_shares' => $remittedShares,
                        'total_remitted' => $remittedAmount + $remittedSavings + $remittedShares,
                        'total_billed' => $billedTotal,
                        'remaining_balance' => $billedTotal - $remittedAmount,
                        'status_class' => ($billedTotal - $remittedAmount) <= 0 ? 'success' : 'warning'
                    ];
                                }
                            }

                            // Process Upload Preview data and merge with existing records
                            if(isset($loansSavingsPreviewPaginated) && $loansSavingsPreviewPaginated->count() > 0) {
                                foreach($loansSavingsPreviewPaginated as $row) {
                                    // Handle both array and object data structures
                                    $memberId = is_array($row) ? ($row['member_id'] ?? null) : ($row->member_id ?? null);
                                    if (!$memberId) continue;

                                    $status = is_array($row) ? ($row['status'] ?? '') : ($row->status ?? '');
                                    $message = is_array($row) ? ($row['message'] ?? '') : ($row->message ?? '');
                                    $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                                    $loans = is_array($row) ? ($row['loans'] ?? 0) : ($row->loans ?? 0);
                                    $savings = is_array($row) ? ($row['savings'] ?? 0) : ($row->savings ?? 0);

                                    $statusClass = $status === 'success' ? 'success' : 'danger';
                                    $isNoBranch = str_contains(strtolower($message), 'no branch');
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
                                            'member_name' => $name,
                                            'billing_type' => 'preview',
                                            'remitted_loans' => $loans,
                                            'remitted_savings' => $savings,
                                            'remitted_shares' => 0,
                                            'total_remitted' => $loans + $savings,
                                            'total_billed' => 0,
                                            'remaining_balance' => 0,
                                            'status_class' => $statusClass
                                        ];
                                    }
                                }
                            }

                            // Process Shares Preview data and merge with existing records
                            if(isset($sharesPreviewPaginated) && $sharesPreviewPaginated->count() > 0) {
                                foreach($sharesPreviewPaginated as $row) {
                                    // Handle both array and object data structures
                                    $memberId = is_array($row) ? ($row['member_id'] ?? null) : ($row->member_id ?? null);
                                    if (!$memberId) continue;

                                    $status = is_array($row) ? ($row['status'] ?? '') : ($row->status ?? '');
                                    $message = is_array($row) ? ($row['message'] ?? '') : ($row->message ?? '');
                                    $name = is_array($row) ? ($row['name'] ?? 'N/A') : ($row->name ?? 'N/A');
                                    $shareAmount = is_array($row) ? ($row['share_amount'] ?? 0) : ($row->share_amount ?? 0);

                                    $statusClass = $status === 'success' ? 'success' : 'danger';
                                    $isNoBranch = str_contains(strtolower($message), 'no branch');
                                    if ($isNoBranch) {
                                        $statusClass = 'no_branch';
                                    }

                                    if (isset($consolidatedData[$memberId])) {
                                        // Merge with existing data
                                        $consolidatedData[$memberId]['remitted_shares'] += $shareAmount;
                                        $consolidatedData[$memberId]['total_remitted'] += $shareAmount;
                                        $consolidatedData[$memberId]['status_class'] = $statusClass;
                                    } else {
                                        // Create new record for shares preview only
                                        $consolidatedData[$memberId] = [
                                            'member_id' => $memberId,
                                            'member_name' => $name,
                                            'billing_type' => 'preview',
                                            'remitted_loans' => 0,
                                            'remitted_savings' => 0,
                                            'remitted_shares' => $shareAmount,
                                            'total_remitted' => $shareAmount,
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

                        {{-- Display Consolidated Data --}}
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

    // Set default value for status filter (show all records by default)
    statusFilter.value = '';

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

            // Filter by status (only "no_branch" now)
            if (status && status === 'no_branch' && rowStatus !== 'no_branch') {
                showRow = false;
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

    // Trigger filter on page load to show default (matched) records
    filterTable();
});
</script>
