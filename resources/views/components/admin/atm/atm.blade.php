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

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Generate Reports</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <a href="{{ route('atm.export.list-of-profile') }}" class="btn btn-success">
                                                <i class="fas fa-file-excel me-1"></i> Export List of Profile
                                            </a>
                                            <a href="{{ route('atm.export.remittance-report-consolidated') }}" class="btn btn-primary">
                                                <i class="fas fa-file-excel me-1"></i> Export Remittance Report Consolidated
                                            </a>
                                            <a href="" class="btn btn-primary">
                                                <i class="fas fa-file-excel me-1"></i> Remittance Report Per Branch
                                            </a>
                                            <a href="" class="btn btn-primary">
                                                <i class="fas fa-file-excel me-1"></i> Remittance Report Per Branch
                                            </a>
                                        </div>
                                    </div>
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
                                <form method="GET" action="{{ route('atm') }}" class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Member Name</label>
                                            <input type="text" class="form-control" name="name"
                                                value="{{ request('name') }}" placeholder="Enter member name">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>EMP ID</label>
                                            <input type="text" class="form-control" name="emp_id"
                                                value="{{ request('emp_id') }}" placeholder="Enter EMP ID">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>CID</label>
                                            <input type="text" class="form-control" name="cid"
                                                value="{{ request('cid') }}" placeholder="Enter CID">
                                        </div>
                                    </div>
                                    <div class="col-md-12 text-right">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                        <a href="{{ route('atm') }}" class="btn btn-secondary">Reset</a> <div class="d-flex align-items-center ms-3">
                                            <a href="{{ route('atm.export-posted-payments') }}" class="btn btn-success me-2">
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
                                                <th>Savings</th>
                                                <th>Share Balance</th>
                                                <th>Loan Balance</th>
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
                                                            <button type="button" class="btn btn-primary btn-sm mb-2 mb-md-0 mr-md-2 w-100" style="min-width: 120px;"
                                                                data-toggle="modal" data-target="#editBalanceModal{{ $member->id }}">
                                                                <i class="fa fa-edit"></i> Edit Balance
                                                            </button>
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
                                                                                        Total Due: ₱{{ number_format($loan->total_due, 2) }} |
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
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Post Loan Payment - {{ $member->lname }}, {{ $member->fname }}</h5>
                                                                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                            </div>
                                                            <form id="postPaymentForm{{ $member->id }}" action="{{ route('atm.post-payment') }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="member_id" value="{{ $member->id }}">
                                                                <div class="modal-body">
                                                                    <!-- Loan Summary Cards -->
                                                                    <div class="row mb-4">
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

                                                                    <!-- Payment Details Form -->
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Payment Amount</label>
                                                                                <input type="number" step="0.01" class="form-control" name="payment_amount" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Payment Date</label>
                                                                                <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Payment Reference</label>
                                                                                <input type="text" class="form-control" name="payment_reference" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Notes</label>
                                                                                <textarea class="form-control" name="notes" rows="1"></textarea>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Loan Details Table -->
                                                                    <div class="row mt-3">
                                                                        <div class="col-12">
                                                                            <div class="card">
                                                                                <div class="card-header">
                                                                                    <h6 class="mb-0">Loan Details</h6>
                                                                                </div>
                                                                                <div class="card-body">
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
                                                                                            Total Due: ₱{{ number_format($loan->total_due, 2) }} |
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
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                    <button type="submit" class="btn btn-primary">Post Payment</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <script>
                                                    $(document).ready(function() {
                                                        $('#postPaymentForm{{ $member->id }}').on('submit', function(e) {
                                                            e.preventDefault();

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
                                                                                timer: 1500
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
                                                                        $('#postPaymentForm{{ $member->id }}')[0].submit();
                                                                    }
                                                                },
                                                                error: function() {
                                                                    // If AJAX fails, submit the form normally
                                                                    $('#postPaymentForm{{ $member->id }}')[0].submit();
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
                                </style>
                                <!-- Pagination -->
                            </div>
                            <div class="d-flex text-center justify-content-center mt-4">
                                {{ $members->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Post Payment Modal -->
                <div class="modal fade" id="postPaymentModal" tabindex="-1" aria-labelledby="postPaymentModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="postPaymentModalLabel">Post Loan Payment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('atm.post-payment') }}" method="POST">
                                @csrf
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="payment_amount" class="form-label">Payment Amount</label>
                                        <input type="number" step="0.01" class="form-control" id="payment_amount" name="payment_amount" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="payment_date" class="form-label">Payment Date</label>
                                        <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="payment_reference" class="form-label">Payment Reference</label>
                                        <input type="text" class="form-control" id="payment_reference" name="payment_reference" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="payment_notes" class="form-label">Notes</label>
                                        <textarea class="form-control" id="payment_notes" name="payment_notes" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Post Payment</button>
                                </div>
                            </form>
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

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Handle payment amount input
            $('input[name="payment_amount"]').on('input', function() {
                var amount = parseFloat($(this).val()) || 0;
                var memberId = $(this).closest('form').find('input[name="member_id"]').val();
                var totalDue = parseFloat($('#totalDue' + memberId).text().replace(/[^0-9.-]+/g, '')) || 0;

                // Update total remittance display
                $(this).closest('.modal').find('.text-success').text('₱' + amount.toFixed(2));
            });
        });
    </script>
    @endpush
</body>

</html>
