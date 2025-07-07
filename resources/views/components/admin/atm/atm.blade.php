<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>ATM Management - Billing and Collection</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
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
        @include('layouts.partials.header')
        @include('layouts.partials.sidebar')

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

                <!-- Information Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <!-- Information Note -->
                                <div class="alert alert-info alert-dismissible fade show mb-0">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <h5><i class="fa fa-info-circle"></i> ATM Management Flow & User Guide</h5>
                                    <ol class="mb-2">
                                        <li><strong>Account Overview:</strong> View all member account balances (savings, shares, loans) across all branches.</li>
                                        <li><strong>Post Payments:</strong> Post loan payments with automatic prioritization (highest priority loans first).</li>
                                        <li><strong>Edit Balances:</strong> Edit account balances for corrections and adjustments.</li>
                                        <li><strong>Export Posted Payments:</strong> Export records of posted payments from this page.</li>
                                    </ol>
                                    <ul class="mb-2">
                                        <li><strong>Search & Filter:</strong> Find members by name, employee ID, or CID across all branches.</li>
                                        <li><strong>Report Generation:</strong> Access comprehensive reports from the Dashboard page.</li>
                                    </ul>
                                    <p class="mb-0"><small><strong>Note:</strong> This is the central hub for managing member accounts and processing payments across all branches. For other reports, visit the Dashboard.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search Section -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="GET" action="{{ route('atm') }}">
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
                                            <a href="{{ route('atm') }}" class="btn btn-secondary mr-2">Reset</a>
                                            <a href="{{ route('atm.export-posted-payments') }}" class="btn btn-success">
                                                <i class="fa fa-file-excel"></i> Export Posted Payments
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Account Balances Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
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
                                            @foreach ($members as $member)
                                                <tr>
                                                    <td>{{ $member->cid }}</td>
                                                    <td>{{ $member->lname }}, {{ $member->fname }}</td>
                                                    <td>{{ $member->branch ? $member->branch->name : 'N/A' }}</td>
                                                    <td>
                                                        @foreach ($member->savings as $saving)
                                                            <div>{{ $saving->account_number }}:
                                                                ₱{{ number_format($saving->current_balance, 2) }}</div>
                                                        @endforeach
                                                    </td>
                                                    <td>
                                                        @foreach ($member->shares as $share)
                                                            <div>{{ $share->account_number }}:
                                                                ₱{{ number_format($share->current_balance, 2) }}</div>
                                                        @endforeach
                                                    </td>
                                                    <td>
                                                        @foreach ($member->loanForecasts as $loan)
                                                            <div>{{ $loan->loan_acct_no }}:
                                                                ₱{{ number_format($loan->total_due, 2) }}</div>
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

                                                <!-- Edit Balance Modal -->
                                                <div class="modal fade" id="editBalanceModal{{ $member->id }}"
                                                    tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog modal-xl" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Account Balance -
                                                                    {{ $member->lname }}, {{ $member->fname }}</h5>
                                                                <button type="button" class="close"
                                                                    data-dismiss="modal"><span>&times;</span></button>
                                                            </div>
                                                            <form action="{{ route('atm.update-balance') }}"
                                                                method="POST">
                                                                @csrf
                                                                <input type="hidden" name="member_id"
                                                                    value="{{ $member->id }}">
                                                                <div class="modal-body">
                                                                    <div class="row">
                                                                        <div class="col-md-4">
                                                                            <div class="card">
                                                                                <div class="card-header">
                                                                                    <h6
                                                                                        class="mb-0"><i class="fa fa-piggy-bank me-2"></i>
                                                                                        Savings Accounts</h6>
                                                                                </div>
                                                                                <div class="card-body">
                                                                                    @foreach ($member->savings as $index => $saving)
                                                                                    <div class="form-group">
                                                                                        <label>{{ $saving->account_number }}</label>
                                                                                        <input type="number"
                                                                                            step="0.01"
                                                                                            class="form-control"
                                                                                            name="savings[{{ $index }}][balance]"
                                                                                            value="{{ $saving->current_balance }}"
                                                                                            required>
                                                                                        <input type="hidden"
                                                                                            name="savings[{{ $index }}][account_number]"
                                                                                            value="{{ $saving->account_number }}">
                                                                                    </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="card">
                                                                                <div class="card-header">
                                                                                    <h6
                                                                                        class="mb-0"><i class="fa fa-chart-pie me-2"></i>
                                                                                        Share Accounts</h6>
                                                                                </div>
                                                                                <div class="card-body">
                                                                                    @foreach ($member->shares as $index => $share)
                                                                                    <div class="form-group">
                                                                                        <label>{{ $share->account_number }}</label>
                                                                                        <input type="number"
                                                                                            step="0.01"
                                                                                            class="form-control"
                                                                                            name="shares[{{ $index }}][balance]"
                                                                                            value="{{ $share->current_balance }}"
                                                                                            required>
                                                                                        <input type="hidden"
                                                                                            name="shares[{{ $index }}][account_number]"
                                                                                            value="{{ $share->account_number }}">
                                                                                    </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="card">
                                                                                <div class="card-header">
                                                                                    <h6
                                                                                        class="mb-0"><i class="fa fa-money-bill me-2"></i>
                                                                                        Loan Accounts</h6>
                                                                                </div>
                                                                                <div class="card-body">
                                                                                    @foreach ($member->loanForecasts as $index => $loan)
                                                                                    <div class="form-group">
                                                                                        <label>{{ $loan->loan_acct_no }}</label>
                                                                                        <input type="number"
                                                                                            step="0.01"
                                                                                            class="form-control"
                                                                                            name="loans[{{ $index }}][balance]"
                                                                                            value="{{ $loan->total_due }}"
                                                                                            required>
                                                                                        <input type="hidden"
                                                                                            name="loans[{{ $index }}][account_number]"
                                                                                            value="{{ $loan->loan_acct_no }}">
                                                                                    </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary"
                                                                        data-dismiss="modal">Close</button>
                                                                    <button type="submit"
                                                                        class="btn btn-primary">Save changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- View Modal -->
                                                <div class="modal fade" id="viewModal{{ $member->id }}"
                                                    tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog modal-xl" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">View Account Details -
                                                                    {{ $member->lname }}, {{ $member->fname }}</h5>
                                                                <button type="button" class="close"
                                                                    data-dismiss="modal"><span>&times;</span></button>
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
                                                                                <h6 class="mb-0">Loan Information</h6>
                                                                            </div>
                                                                            <div class="card-body">
                                                                                <div class="row mb-3">
                                                                                    <div class="col-md-4">
                                                                                        <div class="card bg-light">
                                                                                            <div class="card-body">
                                                                                                <h6 class="card-title">Total Loan Balance</h6>
                                                                                                <h4 class="text-primary">₱{{ number_format($member->loan_balance ?? 0, 2) }}</h4>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="col-md-4">
                                                                                        <div class="card bg-light">
                                                                                            <div class="card-body">
                                                                                                <h6 class="card-title">Total Remittance</h6>
                                                                                                <h4 class="text-success">₱{{ number_format($member->loanForecasts->sum('total_due_after_remittance'), 2) }}</h4>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="col-md-4">
                                                                                        <div class="card bg-light">
                                                                                            <div class="card-body">
                                                                                                <h6 class="card-title">Total Payments</h6>
                                                                                                <h4 class="text-info">₱{{ number_format($member->loanPayments ? $member->loanPayments->sum('amount') : 0, 2) }}</h4>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <h6 class="mb-3">Loan Details</h6>
                                                                                @foreach ($member->loanForecasts as $loan)
                                                                                <div class="loan-info mb-2">
                                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                                        <div>
                                                                                            <strong>{{ $loan->loan_acct_no }}</strong>
                                                                                            <span class="badge {{ $loan->prioritization == 1 ? 'bg-danger' : ($loan->prioritization == 2 ? 'bg-warning' : 'bg-info') }} ms-2">
                                                                                                Priority {{ $loan->prioritization }}
                                                                                            </span>
                                                                                        </div>
                                                                                        <span class="float-right">₱{{ number_format($loan->total_due, 2) }}</span>
                                                                                    </div>
                                                                                    <small class="text-muted">

                                                                                        Principal: ₱{{ number_format($loan->principal_due, 2) }} |
                                                                                        Interest: ₱{{ number_format($loan->interest_due, 2) }} |
                                                                                        Penalty: ₱{{ number_format($loan->penalty_due, 2) }}
                                                                                    </small>
                                                                                    <div class="mt-1">
                                                                                        <small class="text-success">
                                                                                            Remittance: ₱{{ number_format($loan->total_due_after_remittance, 2) }}
                                                                                        </small>
                                                                                    </div>
                                                                                </div>
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

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
                                                            <form id="postPaymentForm{{ $member->id }}" action="{{ route('atm.post-payment') }}" method="POST">
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
                                                                                                Balance: ₱ {{ number_format($loan->total_due, 2) }}
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

                                                        // Function to update payment summary
                                                        function updatePaymentSummary() {
                                                            const withdrawalAmount = parseFloat(withdrawalInput.val()) || 0;
                                                            let totalLoanPayment = 0;

                                                            loanAmountInputs.each(function() {
                                                                const amount = parseFloat($(this).val()) || 0;
                                                                if (amount > 0) {
                                                                    totalLoanPayment += amount;
                                                                }
                                                            });

                                                            const remainingToSavings = withdrawalAmount - totalLoanPayment;

                                                            $('#total-withdrawal' + memberId).text('₱' + withdrawalAmount.toFixed(2));
                                                            $('#total-loan-payment' + memberId).text('₱' + totalLoanPayment.toFixed(2));
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

                                                        // Form submission
                                                        $('#postPaymentForm' + memberId).on('submit', function(e) {
                                                            e.preventDefault();

                                                            const withdrawalAmount = parseFloat(withdrawalInput.val()) || 0;
                                                            let totalLoanPayment = 0;
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

                                                            const remainingToSavings = withdrawalAmount - totalLoanPayment;

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
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

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

                                    .loan-details {
                                        background: #f8f9fa;
                                        padding: 10px;
                                        border-radius: 5px;
                                        border-left: 3px solid #007bff;
                                    }

                                    .loan-details div {
                                        margin-bottom: 5px;
                                    }

                                    .loan-details div:last-child {
                                        margin-bottom: 0;
                                    }

                                    .alert-info {
                                        border-left: 4px solid #17a2b8;
                                    }

                                    .btn {
                                        border-radius: 5px;
                                        font-weight: 500;
                                    }

                                    .btn-primary {
                                        background: linear-gradient(45deg, #007bff, #0056b3);
                                        border: none;
                                    }

                                    .btn-primary:hover {
                                        background: linear-gradient(45deg, #0056b3, #004085);
                                        transform: translateY(-1px);
                                    }

                                    .btn-secondary {
                                        background: linear-gradient(45deg, #6c757d, #545b62);
                                        border: none;
                                    }

                                    .btn-secondary:hover {
                                        background: linear-gradient(45deg, #545b62, #3d4449);
                                        transform: translateY(-1px);
                                    }

                                    .form-check-input:checked {
                                        background-color: #28a745;
                                        border-color: #28a745;
                                    }

                                    .form-check-input:focus {
                                        border-color: #28a745;
                                        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
                                    }

                                    .text-success.fw-bold {
                                        color: #28a745 !important;
                                        font-weight: 700 !important;
                                    }

                                    .text-danger.fw-bold {
                                        color: #dc3545 !important;
                                        font-weight: 700 !important;
                                    }

                                    .modal-header.bg-primary {
                                        background: linear-gradient(45deg, #007bff, #0056b3) !important;
                                    }

                                    .card-header.bg-success {
                                        background: linear-gradient(45deg, #28a745, #1e7e34) !important;
                                    }

                                    .card-header.bg-primary {
                                        background: linear-gradient(45deg, #007bff, #0056b3) !important;
                                    }

                                    .badge {
                                        font-size: 0.75rem;
                                        padding: 0.375rem 0.75rem;
                                    }

                                    .badge.bg-danger {
                                        background: linear-gradient(45deg, #dc3545, #c82333) !important;
                                    }

                                    .badge.bg-warning {
                                        background: linear-gradient(45deg, #ffc107, #e0a800) !important;
                                        color: #212529 !important;
                                    }

                                    .badge.bg-info {
                                        background: linear-gradient(45deg, #17a2b8, #138496) !important;
                                    }
                                </style>
                            </div>
                            <div class="d-flex text-center justify-content-center mt-4">
                                {{ $members->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="copyright">
                <p>Copyright © Designed &amp; Developed by <a href="https://mass-specc.coop/"
                        target="_blank">MASS-SPECC COOPERATIVE</a> 2025</p>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('js/quixnav-init.js') }}"></script>
    <script src="{{ asset('js/custom.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 1500
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '{{ session('error') }}'
            });
        </script>
    @endif
</body>

</html>
