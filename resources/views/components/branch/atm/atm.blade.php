<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>ATM Management - Branch</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon.png') }}">
    <!-- Custom Stylesheet -->
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

</head>
<body>
    <div id="preloader">
        <div class="sk-three-bounce">
            <div class="sk-child sk-bounce1"></div>
            <div class="sk-child sk-bounce2"></div>
            <div class="sk-child sk-bounce3"></div>
        </div>
    </div>

    <div id="main-wrapper">
        <!-- Nav header start -->
        @include('layouts.partials.header')
        <!-- Nav header end -->

        <!-- Sidebar start -->
        @include('layouts.partials.sidebar')
        <!-- Sidebar end -->

        <!-- Content body start -->
        <div class="content-body">
            <div class="container-fluid">
                <div class="row page-titles mx-0">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4>ATM Management</h4>
                            <span class="ml-1">Manage Account Balances</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">ATM Management</li>
                        </ol>
                    </div>
                </div>

                <!-- Search Section -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <!-- Information Note -->
                                <div class="alert alert-info alert-dismissible fade show mb-4">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <h5><i class="fa fa-info-circle"></i> Branch ATM Management Flow & User Guide</h5>
                                    <ol class="mb-2">
                                        <li><strong>Account Overview:</strong> View account for members in your branch only.</li>
                                        <li><strong>Post Payments:</strong> Post loan payments with automatic prioritization (highest priority loans first).</li>
                                        <li><strong>Export Reports:</strong> Export posted payment records for your branch.</li>
                                    </ol>
                                    <ul class="mb-2">
                                        <li><strong>Search:</strong> Find members by name, employee ID, or CID within your branch.</li>
                                        <li><strong>Branch-Specific:</strong> All operations and reports are limited to your branch's member data.</li>
                                    </ul>
                                    <p class="mb-0"><small><strong>Note:</strong> This page allows you to manage member accounts and process payments specifically for your branch.</small></p>
                                </div>

                                <form method="GET" action="{{ route('branch.atm') }}">
                                    <div class="form-row align-items-end">
                                        <div class="col-md-3">
                                            <div class="form-group mb-0">
                                                <label>Member Name</label>
                                                <input type="text" class="form-control" name="name"
                                                    value="{{ request('name') }}" placeholder="Enter member name">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-0">
                                                <label>EMP ID</label>
                                                <input type="text" class="form-control" name="emp_id"
                                                    value="{{ request('emp_id') }}" placeholder="Enter EMP ID">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-0">
                                                <label>CID</label>
                                                <input type="text" class="form-control" name="cid"
                                                    value="{{ request('cid') }}" placeholder="Enter CID">
                                            </div>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary mr-2">Search</button>
                                            <a href="{{ route('branch.atm') }}" class="btn btn-secondary mr-2">Reset</a>
                                            {{-- <a href="{{ route('branch.atm.export-posted-payments') }}" class="btn btn-success">
                                                <i class="fa fa-file-excel"></i> Export Posted Payments
                                            </a> --}}
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>


                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Handle first export form
                        var allDatesCheckbox = document.getElementById('all_dates');
                        var dateInput = document.getElementById('export_date');
                        if (allDatesCheckbox && dateInput) {
                            allDatesCheckbox.addEventListener('change', function() {
                                dateInput.disabled = this.checked;
                            });
                        }

                        // Handle second export form
                        var allDatesDetailedCheckbox = document.getElementById('all_dates_detailed');
                        var dateDetailedInput = document.getElementById('export_date_detailed');
                        if (allDatesDetailedCheckbox && dateDetailedInput) {
                            allDatesDetailedCheckbox.addEventListener('change', function() {
                                dateDetailedInput.disabled = this.checked;
                            });
                        }
                    });
                </script>

                <!-- Account Balances Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card p-2">
                             <!-- Export Posted Payments Filter Form -->
                <div class="row mb-2">
                    <div class="col-md-6">
                        <form method="GET" action="{{ route('branch.atm.export-posted-payments') }}" class="form-inline" id="exportPostedPaymentsForm">
                            <div class="form-group mr-2">
                                <label for="export_date" class="mr-2">Export Date:</label>
                                <input type="date" id="export_date" name="date" class="form-control" value="{{ request('date', date('Y-m-d')) }}" @if(request('all_dates')) disabled @endif>
                            </div>
                            <div class="form-group mr-2">
                                <input type="checkbox" id="all_dates" name="all_dates" value="1" {{ request('all_dates') ? 'checked' : '' }}>
                                <label for="all_dates" class="ml-1">All Dates</label>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-file-excel"></i> Export Posted Payments
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" action="{{ route('branch.atm.generate-batch-report') }}" class="form-inline" id="generateBatchReportForm">
                            <div class="form-group mr-2">
                                <label for="batch_report_date" class="mr-2">Report Date:</label>
                                <input type="date" id="batch_report_date" name="date" class="form-control" value="{{ request('date', date('Y-m-d')) }}" @if(request('all_dates_batch')) disabled @endif>
                            </div>
                            <div class="form-group mr-2">
                                <input type="checkbox" id="all_dates_batch" name="all_dates" value="1" {{ request('all_dates_batch') ? 'checked' : '' }}>
                                <label for="all_dates_batch" class="ml-1">All Dates</label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-file-pdf"></i> ATM Batch Report
                            </button>
                        </form>
                    </div>
                    {{-- <div class="col-md-6">
                        <form method="GET" action="{{ route('branch.atm.export-posted-payments-detailed') }}" class="form-inline" id="exportPostedPaymentsDetailedForm">
                            <div class="form-group mr-2">
                                <label for="export_date_detailed" class="mr-2">Export Date:</label>
                                <input type="date" id="export_date_detailed" name="date" class="form-control" value="{{ request('date', date('Y-m-d')) }}" @if(request('all_dates_detailed')) disabled @endif>
                            </div>
                            <div class="form-group mr-2">
                                <input type="checkbox" id="all_dates_detailed" name="all_dates" value="1" {{ request('all_dates') ? 'checked' : '' }}>
                                <label for="all_dates_detailed" class="ml-1">All Dates</label>
                            </div>
                            <button type="submit" class="btn btn-info">
                                <i class="fa fa-file-excel"></i> Export with Principal/Penalty/Interest
                            </button>
                        </form>
                    </div> --}}
                </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>CID</th>
                                                <th>Name</th>
                                                <th>Branch</th>
                                                <th>Savings Accounts</th>
                                                <th>Share Accounts</th>
                                                <th>Loan Accounts</th>

                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($members as $member)
                                                <tr>
                                                    <td>{{ $member->cid }}</td>
                                                    <td>{{ $member->lname }}, {{ $member->fname }}</td>
                                                    <td>{{ $member->branch ? $member->branch->name : 'N/A' }}</td>
                                                    <td>
                                                        @foreach ($member->savings as $saving)
                                                            <div>{{ $saving->account_number }},
                                                            </div>
                                                        @endforeach
                                                    </td>
                                                    <td>
                                                        @foreach ($member->shares as $share)
                                                            <div>{{ $share->account_number }},
                                                            </div>
                                                        @endforeach
                                                    </td>
                                                    <td>
                                                        @foreach ($member->loanForecasts as $index => $loan)
                                                            @if (floatval($loan->total_due) > 0)
                                                                <div>{{ $loan->loan_acct_no }}
                                                                    ₱{{ number_format($loan->total_due, 2) }},</div>
                                                            @endif
                                                        @endforeach
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column flex-md-row align-items-stretch">
                                                            <button type="button" class="btn btn-success btn-sm mb-2 mb-md-0 mr-md-2 w-100" style="min-width: 120px;"
                                                                data-toggle="modal" data-target="#postPaymentModal{{ $member->id }}">
                                                                <i class="fa fa-money-bill"></i> Post Payment
                                                            </button>
                                                            {{-- <button type="button" class="btn btn-primary btn-sm mb-2 mb-md-0 mr-md-2 w-100" style="min-width: 120px;"
                                                                data-toggle="modal" data-target="#editBalanceModal{{ $member->id }}">
                                                                <i class="fa fa-edit"></i> Edit Balance
                                                            </button> --}}
                                                            <button type="button" class="btn btn-info btn-sm w-100" style="min-width: 120px;"
                                                                data-toggle="modal" data-target="#viewModal{{ $member->id }}">
                                                                <i class="fa fa-eye"></i> View
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <!-- Post Payment Modal -->
                                                <div class="modal fade" id="postPaymentModal{{ $member->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog modal-xl">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-primary text-white">
                                                                <h5 class="modal-title">
                                                                    <i class="fa fa-money-bill me-2"></i>
                                                                    Post ATM Withdrawal - {{ $member->lname }}, {{ $member->fname }}
                                                                </h5>
                                                                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                                                            </div>
                                                            <form id="postPaymentForm{{ $member->id }}" action="{{ route('branch.atm.post-payment') }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="member_id" value="{{ $member->id }}">
                                                                <div class="modal-body">
                                                                    <!-- Loan Summary Cards -->
                                                                    <div class="row mb-4">
                                                                        <div class="col-md-4">
                                                                            <div class="card bg-light border-primary">
                                                                                <div class="card-body text-center">
                                                                                    <h6 class="card-title text-primary">
                                                                                        <i class="fa fa-credit-card me-2"></i>Total Loan Balance
                                                                                    </h6>
                                                                                    <h4 class="text-primary">₱{{ number_format($member->loan_balance ?? 0, 2) }}</h4>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="card bg-light border-success">
                                                                                <div class="card-body text-center">
                                                                                    <h6 class="card-title text-success">
                                                                                        <i class="fa fa-exchange-alt me-2"></i>Total Remittance
                                                                                    </h6>
                                                                                    <h4 class="text-success">₱{{ number_format($member->loanForecasts->sum('total_due_after_remittance'), 2) }}</h4>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="card bg-light border-info">
                                                                                <div class="card-body text-center">
                                                                                    <h6 class="card-title text-info">
                                                                                        <i class="fa fa-money-check-alt me-2"></i>Total Payments
                                                                                    </h6>
                                                                                    <h4 class="text-info">₱{{ number_format($member->loanPayments ? $member->loanPayments->sum('amount') : 0, 2) }}</h4>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Withdrawal Amount Field -->
                                                                    <div class="form-group">
                                                                        <label class="form-label fw-bold">
                                                                            <i class="fa fa-cash-register me-2 text-primary"></i>Withdrawal Amount
                                                                        </label>
                                                                        <input type="number" step="0.01" class="form-control form-control-lg"
                                                                               name="withdrawal_amount" id="withdrawal_amount{{ $member->id }}"
                                                                               placeholder="Enter the total amount withdrawn from ATM" required>
                                                                        <small class="form-text text-muted">
                                                                            <i class="fa fa-info-circle me-1"></i>
                                                                            Enter the total amount withdrawn from ATM
                                                                        </small>
                                                                    </div>

                                                                    <!-- Savings Post Payment Section -->
                                                                    <div class="row mb-4">
                                                                        <div class="col-12">
                                                                            <div class="card bg-light border-success">
                                                                                <div class="card-body">
                                                                                    <h6 class="card-title text-success mb-3">
                                                                                        <i class="fa fa-piggy-bank me-2"></i>Post Payment for Savings
                                                                                    </h6>
                                                                                    <div class="row">
                                                                                        @foreach ($member->savings as $saving)
                                                                                            <div class="col-md-6 mb-2">
                                                                                                <label class="form-label small">
                                                                                                    <span class="fw-bold">{{ $saving->savingProduct->product_name ?? 'Unknown Product' }}</span>
                                                                                                    <span class="ml-2">({{ $saving->account_number }})</span>
                                                                                                </label>
                                                                                                <input type="number" step="0.01" class="form-control savings-amount-input" name="savings_amounts[{{ $saving->account_number }}]" placeholder="Enter deposit amount" data-member-id="{{ $member->id }}" data-product-name="{{ $saving->savingProduct->product_name ?? '' }}">
                                                                                            </div>
                                                                                        @endforeach
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Loan Selection Section -->
                                                                    <div class="form-group mt-4">
                                                                        <label class="form-label fw-bold">
                                                                            <i class="fa fa-list-check me-2 text-success"></i>Select Loans to Pay
                                                                        </label>
                                                                        <div class="alert alert-info">
                                                                            <i class="fa fa-lightbulb me-2"></i>
                                                                            <strong>Tip:</strong> You can directly enter amounts for loans you want to pay, or check the boxes to auto-fill with total due amounts.
                                                                            Remaining amount will be added to Regular Savings if available.
                                                                        </div>
                                                                        <div class="loan-selection-container" id="loan-selection{{ $member->id }}">
                                                                            @foreach ($member->loanForecasts as $index => $loan)
                                                                                @if (floatval($loan->total_due) > 0)
                                                                                    <div class="loan-option mb-3 p-3 border rounded">
                                                                                        <div class="form-check">
                                                                                            <input class="form-check-input loan-checkbox" type="checkbox"
                                                                                                   name="selected_loans[]"
                                                                                                   value="{{ $loan->loan_acct_no }}"
                                                                                                   id="loan{{ $member->id }}_{{ $index }}"
                                                                                                   data-total-due="{{ $loan->total_due }}"
                                                                                                   data-member-id="{{ $member->id }}">
                                                                                            <label class="form-check-label fw-bold" for="loan{{ $member->id }}_{{ $index }}">
                                                                                                <i class="fa fa-credit-card me-2 text-primary"></i>
                                                                                                <strong>{{ $loan->loan_acct_no }}</strong>
                                                                                                <span class="badge bg-info ms-2">
                                                                                                    Amort Due: ₱ {{ number_format($loan->total_due, 2) }}
                                                                                                </span>
                                                                                            </label>
                                                                                        </div>
                                                                                        <div class="ml-4 mt-2">
                                                                                            <div class="row">
                                                                                                <div class="col-md-6">
                                                                                                    <label class="form-label small">Amount to Pay:</label>
                                                                                                    <input type="number" step="0.01" class="form-control loan-amount-input"
                                                                                                           name="loan_amounts[{{ $loan->loan_acct_no }}]"
                                                                                                           placeholder="Enter amount"
                                                                                                           data-total-due="{{ $loan->total_due }}"
                                                                                                           data-member-id="{{ $member->id }}">
                                                                                                </div>
                                                                                                <div class="col-md-6">
                                                                                                    <div class="loan-details small text-muted">
                                                                                                        <div><strong>Principal:</strong> ₱{{ number_format($loan->principal_due, 2) }}</div>
                                                                                                        <div><strong>Interest:</strong> ₱{{ number_format($loan->interest_due, 2) }}</div>
                                                                                                        <div><strong>Penalty:</strong> ₱{{ number_format($loan->penalty_due, 2) }}</div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                @endif
                                                                            @endforeach
                                                                        </div>
                                                                    </div>

                                                                    <!-- Payment Summary -->
                                                                    <div class="row mt-4">
                                                                        <div class="col-md-12">
                                                                            <div class="card bg-light border-primary">
                                                                                <div class="card-header bg-primary text-white">
                                                                                    <h6 class="card-title mb-0">
                                                                                        <i class="fa fa-file-invoice me-2"></i>Payment Details
                                                                                    </h6>
                                                                                </div>
                                                                                <div class="card-body">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Payment Date</label>
                                                                                        <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                                                                                    </div>
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Payment Reference</label>
                                                                                        <input type="text" class="form-control" name="payment_reference" placeholder="Enter reference number" required>
                                                                                    </div>
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Notes</label>
                                                                                        <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes"></textarea>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer bg-light">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                                        <i class="fa fa-times me-2"></i>Cancel
                                                                    </button>
                                                                    <button type="submit" class="btn btn-primary">
                                                                        <i class="fa fa-check me-2"></i>Process Payment
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <script>
                                                    $(document).ready(function() {
                                                        const memberId = {{ $member->id }};
                                                        const withdrawalInput = $('#withdrawal_amount' + memberId);
                                                        const loanCheckboxes = $('.loan-checkbox[data-member-id="' + memberId + '"]');
                                                        const loanAmountInputs = $('.loan-amount-input[data-member-id="' + memberId + '"]');
                                                        const savingsAmountInputs = $('.savings-amount-input[data-member-id="' + memberId + '"]');

                                                        // Hide loan options with zero balance when modal is shown
                                                        $('#postPaymentModal{{ $member->id }}').on('shown.bs.modal', function () {
                                                            $(this).find('.loan-option').each(function() {
                                                                var balanceText = $(this).find('.badge.bg-info').text();
                                                                var match = balanceText.match(/Balance: ₱\s*([\d,.]+)/);
                                                                var balance = match ? parseFloat(match[1].replace(/,/g, '')) : 0;
                                                                if (balance === 0) {
                                                                    $(this).hide();
                                                                } else {
                                                                    $(this).show();
                                                                }
                                                            });
                                                        });

                                                        // Function to update payment summary
                                                        function updatePaymentSummary() {
                                                            const withdrawalAmount = parseFloat(withdrawalInput.val()) || 0;
                                                            let totalLoanPayment = 0;
                                                            let totalSavingsDeposit = 0;

                                                            loanAmountInputs.each(function() {
                                                                const amount = parseFloat($(this).val()) || 0;
                                                                if (amount > 0) {
                                                                    totalLoanPayment += amount;
                                                                }
                                                            });

                                                            savingsAmountInputs.each(function() {
                                                                const amount = parseFloat($(this).val()) || 0;
                                                                if (amount > 0) {
                                                                    totalSavingsDeposit += amount;
                                                                }
                                                            });

                                                            const remainingToSavings = withdrawalAmount - totalLoanPayment - totalSavingsDeposit;

                                                            $('#total-withdrawal' + memberId).text('₱' + withdrawalAmount.toFixed(2));
                                                            $('#total-loan-payment' + memberId).text('₱' + totalLoanPayment.toFixed(2));
                                                            $('#total-savings-deposit' + memberId).text('₱' + totalSavingsDeposit.toFixed(2));
                                                            $('#remaining-to-savings' + memberId).text('₱' + remainingToSavings.toFixed(2));

                                                            // Highlight if remaining amount is negative
                                                            if (remainingToSavings < 0) {
                                                                $('#remaining-to-savings' + memberId).removeClass('text-success').addClass('text-danger fw-bold');
                                                            } else if (remainingToSavings > 0) {
                                                                $('#remaining-to-savings' + memberId).removeClass('text-danger').addClass('text-success fw-bold');
                                                            } else {
                                                                $('#remaining-to-savings' + memberId).removeClass('text-danger text-success fw-bold');
                                                            }
                                                        }

                                                        // Handle withdrawal amount change
                                                        withdrawalInput.on('input', updatePaymentSummary);

                                                        // Handle loan checkbox changes
                                                        loanCheckboxes.on('change', function() {
                                                            const checkbox = $(this);
                                                            const loanOption = checkbox.closest('.loan-option');
                                                            const amountInput = loanOption.find('.loan-amount-input');

                                                            if (checkbox.is(':checked')) {
                                                                loanOption.addClass('border-success').removeClass('border-secondary');
                                                                // Auto-fill with total due amount if input is empty
                                                                if (!amountInput.val()) {
                                                                    const totalDue = parseFloat(amountInput.data('total-due')) || 0;
                                                                    amountInput.val(totalDue.toFixed(2));
                                                                }
                                                            } else {
                                                                loanOption.removeClass('border-success').addClass('border-secondary');
                                                                // Don't clear the amount, just uncheck the box
                                                            }

                                                            updatePaymentSummary();
                                                        });

                                                        // Handle loan amount input changes
                                                        loanAmountInputs.on('input', function() {
                                                            const input = $(this);
                                                            const totalDue = parseFloat(input.data('total-due')) || 0;
                                                            const enteredAmount = parseFloat(input.val()) || 0;
                                                            const checkbox = input.closest('.loan-option').find('.loan-checkbox');

                                                            // Auto-check the checkbox when user enters an amount
                                                            if (enteredAmount > 0 && !checkbox.is(':checked')) {
                                                                checkbox.prop('checked', true).trigger('change');
                                                            }

                                                            // Validate amount doesn't exceed total due
                                                            if (enteredAmount > totalDue) {
                                                                input.val(totalDue.toFixed(2));
                                                                Swal.fire({
                                                                    icon: 'warning',
                                                                    title: 'Amount Limit',
                                                                    text: 'Amount cannot exceed total due for this loan'
                                                                });
                                                            }

                                                            updatePaymentSummary();
                                                        });

                                                        // Handle savings amount input changes
                                                        savingsAmountInputs.on('input', function() {
                                                            const input = $(this);
                                                            const amount = parseFloat(input.val()) || 0;
                                                            const memberId = input.data('member-id');
                                                            const productName = input.data('product-name');

                                                            // Update the corresponding input in the form
                                                            $('#postPaymentForm' + memberId).find(`input[name="savings_amounts[${input.attr('name').split('[')[1].replace(']', '')}]"]`).val(amount.toFixed(2));

                                                            updatePaymentSummary();
                                                        });

                                                        // Form submission
                                                        $('#postPaymentForm' + memberId).on('submit', function(e) {
                                                            e.preventDefault();

                                                            const withdrawalAmount = parseFloat(withdrawalInput.val()) || 0;
                                                            let totalLoanPayment = 0;
                                                            let totalSavingsDeposit = 0;
                                                            let hasSelectedLoans = false;

                                                            // Check which loans have amounts entered
                                                            loanAmountInputs.each(function() {
                                                                const amount = parseFloat($(this).val()) || 0;
                                                                if (amount > 0) {
                                                                    totalLoanPayment += amount;
                                                                    hasSelectedLoans = true;
                                                                    // Ensure the checkbox is checked for this loan
                                                                    $(this).closest('.loan-option').find('.loan-checkbox').prop('checked', true);
                                                                } else {
                                                                    // Uncheck checkbox and disable input for loans with no amount
                                                                    $(this).closest('.loan-option').find('.loan-checkbox').prop('checked', false);
                                                                    $(this).prop('disabled', true);
                                                                }
                                                            });

                                                            // Check which savings have amounts entered
                                                            savingsAmountInputs.each(function() {
                                                                const amount = parseFloat($(this).val()) || 0;
                                                                if (amount > 0) {
                                                                    totalSavingsDeposit += amount;
                                                                }
                                                            });

                                                            const remainingToSavings = withdrawalAmount - totalLoanPayment - totalSavingsDeposit;

                                                            // Validation
                                                            if (withdrawalAmount <= 0) {
                                                                Swal.fire({
                                                                    icon: 'error',
                                                                    title: 'Invalid Amount',
                                                                    text: 'Please enter a valid withdrawal amount'
                                                                });
                                                                return;
                                                            }

                                                            if (!hasSelectedLoans) {
                                                                Swal.fire({
                                                                    icon: 'error',
                                                                    title: 'No Loans Selected',
                                                                    text: 'Please enter amounts for at least one loan'
                                                                });
                                                                return;
                                                            }

                                                            if (remainingToSavings < 0) {
                                                                Swal.fire({
                                                                    icon: 'error',
                                                                    title: 'Invalid Amount',
                                                                    text: 'Total loan payment amount cannot exceed withdrawal amount'
                                                                });
                                                                return;
                                                            }

                                                            // Show confirmation dialog
                                                            Swal.fire({
                                                                icon: 'question',
                                                                title: 'Confirm Payment',
                                                                html: `
                                                                    <div class="text-left">
                                                                        <p><strong>Withdrawal Amount:</strong> ₱${withdrawalAmount.toFixed(2)}</p>
                                                                        <p><strong>Total Loan Payment:</strong> ₱${totalLoanPayment.toFixed(2)}</p>
                                                                        <p><strong>Total Savings Deposit:</strong> ₱${totalSavingsDeposit.toFixed(2)}</p>
                                                                        <p><strong>Remaining to Savings:</strong> ₱${remainingToSavings.toFixed(2)}</p>
                                                                    </div>
                                                                `,
                                                                showCancelButton: true,
                                                                confirmButtonText: 'Process Payment',
                                                                cancelButtonText: 'Cancel'
                                                            }).then((result) => {
                                                                if (result.isConfirmed) {
                                                                    // Submit form
                                                                    $.ajax({
                                                                        url: $(this).attr('action'),
                                                                        method: 'POST',
                                                                        data: $(this).serialize(),
                                                                        success: function(response) {
                                                                            try {
                                                                                const result = JSON.parse(response);
                                                                                if (result.success) {
                                                                                    Swal.fire({
                                                                                        icon: 'success',
                                                                                        title: 'Success!',
                                                                                        text: result.message,
                                                                                        showConfirmButton: false,
                                                                                        timer: 2000
                                                                                    }).then(function() {
                                                                                        location.reload();
                                                                                    });
                                                                                } else {
                                                                                    Swal.fire({
                                                                                        icon: 'error',
                                                                                        title: 'Error!',
                                                                                        text: result.message
                                                                                    });
                                                                                }
                                                                            } catch (e) {
                                                                                // If response is not JSON, just submit the form normally
                                                                                $('#postPaymentForm' + memberId)[0].submit();
                                                                            }
                                                                        },
                                                                        error: function() {
                                                                            // If AJAX fails, submit the form normally
                                                                            $('#postPaymentForm' + memberId)[0].submit();
                                                                        }
                                                                    });
                                                                }
                                                            });
                                                        });
                                                    });
                                                </script>

                                                <!-- Edit Balance Modal -->
                                                <div class="modal fade" id="editBalanceModal{{ $member->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog modal-xl" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Account Balance - {{ $member->lname }}, {{ $member->fname }}</h5>
                                                                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                            </div>
                                                            <form action="{{ route('branch.atm.update-balance') }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="member_id" value="{{ $member->id }}">
                                                                <div class="modal-body">
                                                                    <div class="row">
                                                                        <div class="col-md-4">
                                                                            <div class="card">
                                                                                <div class="card-header">
                                                                                    <h6 class="mb-0"><i class="fa fa-piggy-bank me-2"></i>Savings Accounts</h6>
                                                                                </div>
                                                                                <div class="card-body">
                                                                                    @foreach ($member->savings as $index => $saving)
                                                                                    <div class="form-group">
                                                                                        <label>{{ $saving->account_number }}</label>
                                                                                        <input type="number" step="0.01" class="form-control"
                                                                                            name="savings[{{ $index }}][balance]"
                                                                                            value="{{ $saving->current_balance }}" required>
                                                                                        <input type="hidden" name="savings[{{ $index }}][account_number]"
                                                                                            value="{{ $saving->account_number }}">
                                                                                    </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="card">
                                                                                <div class="card-header">
                                                                                    <h6 class="mb-0"><i class="fa fa-chart-pie me-2"></i>Share Accounts</h6>
                                                                                </div>
                                                                                <div class="card-body">
                                                                                    @foreach ($member->shares as $index => $share)
                                                                                    <div class="form-group">
                                                                                        <label>{{ $share->account_number }}</label>
                                                                                        <input type="number" step="0.01" class="form-control"
                                                                                            name="shares[{{ $index }}][balance]"
                                                                                            value="{{ $share->current_balance }}" required>
                                                                                        <input type="hidden" name="shares[{{ $index }}][account_number]"
                                                                                            value="{{ $share->account_number }}">
                                                                                    </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="card">
                                                                                <div class="card-header">
                                                                                    <h6 class="mb-0"><i class="fa fa-money-bill me-2"></i>Loan Accounts</h6>
                                                                                </div>
                                                                                <div class="card-body">
                                                                                    @foreach ($member->loanForecasts as $index => $loan)
                                                                                    <div class="form-group">
                                                                                        <label>{{ $loan->loan_acct_no }}</label>
                                                                                        <input type="number" step="0.01" class="form-control"
                                                                                            name="loans[{{ $index }}][balance]"
                                                                                            value="{{ $loan->total_due }}" required>
                                                                                        <input type="hidden" name="loans[{{ $index }}][account_number]"
                                                                                            value="{{ $loan->loan_acct_no }}">
                                                                                    </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- View Modal -->
                                                <div class="modal fade" id="viewModal{{ $member->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog modal-xl" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">View Account Details - {{ $member->lname }}, {{ $member->fname }}</h5>
                                                                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-4">
                                                                        <div class="card">
                                                                            <div class="card-header">
                                                                                <h6 class="mb-0">Member Information</h6>
                                                                            </div>
                                                                            <div class="card-body">
                                                                                <p><strong>CID:</strong> {{ $member->cid }}</p>
                                                                                <p><strong>EMP ID:</strong> {{ $member->emp_id }}</p>
                                                                                <p><strong>Branch:</strong> {{ $member->branch ? $member->branch->name : 'N/A' }}</p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="card">
                                                                            <div class="card-header">
                                                                                <h6 class="mb-0">Savings Accounts</h6>
                                                                            </div>
                                                                            <div class="card-body">
                                                                                @foreach ($member->savings as $saving)
                                                                                <div class="account-details mb-3">
                                                                                    <p><strong>Account Number:</strong> {{ $saving->account_number }}</p>
                                                                                    <p><strong>Current Balance:</strong> ₱{{ number_format($saving->current_balance, 2) }}</p>
                                                                                    <p><strong>Available Balance:</strong> ₱{{ number_format($saving->available_balance, 2) }}</p>
                                                                                    <p><strong>Open Date:</strong> {{ $saving->open_date }}</p>
                                                                                </div>
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="card">
                                                                            <div class="card-header">
                                                                                <h6 class="mb-0">Share Accounts</h6>
                                                                            </div>
                                                                            <div class="card-body">
                                                                                @foreach ($member->shares as $share)
                                                                                <div class="account-details mb-3">
                                                                                    <p><strong>Account Number:</strong> {{ $share->account_number }}</p>
                                                                                    <p><strong>Current Balance:</strong> ₱{{ number_format($share->current_balance, 2) }}</p>
                                                                                    <p><strong>Available Balance:</strong> ₱{{ number_format($share->available_balance, 2) }}</p>
                                                                                    <p><strong>Open Date:</strong> {{ $share->open_date }}</p>
                                                                                </div>
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row mt-4">
                                                                    <div class="col-12">
                                                                        <div class="card">
                                                                            <div class="card-header">
                                                                                <h6 class="mb-0">Loan Accounts</h6>
                                                                            </div>
                                                                            <div class="card-body">
                                                                                <div class="row">
                                                                                    @foreach ($member->loanForecasts as $loan)
                                                                                    <div class="col-md-6 mb-3">
                                                                                        <div class="card">
                                                                                            <div class="card-body">
                                                                                                <h6 class="card-title">{{ $loan->loan_acct_no }}</h6>
                                                                                                <p><strong>Total Due:</strong> ₱{{ number_format($loan->total_due, 2) }}</p>
                                                                                                <p><strong>Principal Due:</strong> ₱{{ number_format($loan->principal_due, 2) }}</p>
                                                                                                <p><strong>Interest Due:</strong> ₱{{ number_format($loan->interest_due, 2) }}</p>
                                                                                                <p><strong>Penalty Due:</strong> ₱{{ number_format($loan->penalty_due, 2) }}</p>
                                                                                                <p><strong>Open Date:</strong> {{ $loan->open_date }}</p>
                                                                                                <p><strong>Maturity Date:</strong> {{ $loan->maturity_date }}</p>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">No members found for your search.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex text-center justify-content-center mt-4">
                                    {{ $members->appends(request()->query())->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Content body end -->

        <!-- Footer start -->
        @include('layouts.partials.footer')
        <!-- Footer end -->
    </div>

    <!-- Required vendors -->
    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('js/custom.min.js') }}"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        .flex.justify-between.flex-1.sm\:hidden {
            display: none;
        }

        /* Custom styles for ATM modal */
        .loan-option {
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .loan-option:hover {
            background: #e9ecef;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .loan-option.border-success {
            background: #d1edff;
            border-color: #28a745 !important;
        }

        .loan-option.border-secondary {
            background: #f8f9fa;
            border-color: #6c757d !important;
        }

        .form-control-lg {
            font-size: 1.1rem;
            padding: 0.75rem 1rem;
        }

        .card-header {
            border-bottom: none;
        }

        .modal-xl {
            max-width: 1200px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle export form
            var allDatesCheckbox = document.getElementById('all_dates');
            var dateInput = document.getElementById('export_date');
            if (allDatesCheckbox && dateInput) {
                allDatesCheckbox.addEventListener('change', function() {
                    dateInput.disabled = this.checked;
                });
            }

            // Handle batch report form
            var allDatesBatchCheckbox = document.getElementById('all_dates_batch');
            var dateBatchInput = document.getElementById('batch_report_date');
            if (allDatesBatchCheckbox && dateBatchInput) {
                allDatesBatchCheckbox.addEventListener('change', function() {
                    dateBatchInput.disabled = this.checked;
                });
            }
        });
    </script>
</body>
</html>
