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
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('atm.export.list-of-profile') }}" class="btn btn-success">
                                                <i class="fas fa-file-excel me-1"></i> Export List of Profile
                                            </a>
                                            <a href="{{ route('atm.export.remittance-report-consolidated') }}" class="btn btn-primary">
                                                <i class="fas fa-file-excel me-1"></i> Export Remittance Report Consolidated
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
                                        <a href="{{ route('atm') }}" class="btn btn-secondary">Reset</a>
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
                                                        <button type="button" class="btn btn-primary btn-sm"
                                                            data-toggle="modal"
                                                            data-target="#editBalanceModal{{ $member->id }}">
                                                            <i class="fa fa-edit"></i> Edit Balance
                                                        </button>
                                                        <button type="button" class="btn btn-success btn-sm"
                                                            data-toggle="modal"
                                                            data-target="#postPaymentModal{{ $member->id }}">
                                                            <i class="fa fa-money-bill"></i> Post Payment
                                                        </button>
                                                        <button type="button" class="btn btn-info btn-sm"
                                                            data-toggle="modal"
                                                            data-target="#viewModal{{ $member->id }}">
                                                            <i class="fa fa-eye"></i> View
                                                        </button>
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
                                                                                <h6 class="mb-0">Loan Accounts</h6>
                                                                            </div>
                                                                            <div class="card-body">
                                                                                <div class="row">
                                                                                    @foreach ($member->loanForecasts as $loan)
                                                                                    <div class="col-md-4">
                                                                                        <div class="account-details mb-3">
                                                                                            <p><strong>Loan Account No:</strong> {{ $loan->loan_acct_no }}</p>
                                                                                            <p><strong>Total Due:</strong> ₱{{ number_format($loan->total_due, 2) }}</p>
                                                                                            <p><strong>Principal Due:</strong> ₱{{ number_format($loan->principal_due, 2) }}</p>
                                                                                            <p><strong>Interest Due:</strong> ₱{{ number_format($loan->interest_due, 2) }}</p>
                                                                                            <p><strong>Penalty Due:</strong> ₱{{ number_format($loan->penalty_due, 2) }}</p>
                                                                                            <p><strong>Open Date:</strong> {{ $loan->open_date }}</p>
                                                                                            <p><strong>Maturity Date:</strong> {{ $loan->maturity_date }}</p>
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
                                                            <form action="{{ route('atm.post-payment') }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="member_id" value="{{ $member->id }}">
                                                                <div class="modal-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label>Payment Amount</label>
                                                                                <input type="number" step="0.01" class="form-control" name="payment_amount" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label>Payment Date</label>
                                                                                <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label>Payment Reference</label>
                                                                                <input type="text" class="form-control" name="payment_reference" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label>Notes</label>
                                                                                <textarea class="form-control" name="notes" rows="1"></textarea>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row mt-3">
                                                                        <div class="col-12">
                                                                            <div class="card">
                                                                                <div class="card-header">
                                                                                    <h6 class="mb-0">Current Loan Balances</h6>
                                                                                </div>
                                                                                <div class="card-body">
                                                                                    @foreach ($member->loanForecasts as $loan)
                                                                                    <div class="loan-info mb-2">
                                                                                        <strong>{{ $loan->loan_acct_no }}:</strong>
                                                                                        <span class="float-right">₱{{ number_format($loan->total_due, 2) }}</span>
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
                title: 'Success',
                text: '{{ session('success') }}',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}'
            });
        </script>
    @endif
</body>

</html>
