<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Remittance Records - Billing and Collection</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">
    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <style>
        .table-card {
            margin-bottom: 1.5rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .table-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }
        .table th {
            background-color: #f3f6f9;
            font-weight: 600;
        }
        .badge-status {
            min-width: 85px;
            padding: 5px 10px;
        }
        .member-info {
            line-height: 1.2;
        }
        .member-id {
            color: #666;
            font-size: 0.85rem;
        }
        .amount-col {
            font-family: monospace;
            font-size: 1.1rem;
        }
    </style>
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
                <div class="row page-titles mx-0 mb-3">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4>Remittance Records</h4>
                            <span class="ml-1">View and manage remittance transactions</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Remittance Records</li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card table-card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h4 class="card-title mb-0">
                                        <i class="fa fa-list-alt text-primary"></i> Recent Remittance Records
                                    </h4>
                                   
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="remittanceTable" class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Member</th>
                                                <th>Branch</th>
                                                <th class="text-right">Loan Payment</th>
                                                <th style="width: 100px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($remittances as $remittance)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $remittance->created_at->format('M d, Y') }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $remittance->created_at->format('h:i A') }}</small>
                                                    </td>
                                                    <td>
                                                        @if($remittance->member)
                                                            <div class="member-info">
                                                                <strong>{{ $remittance->member->fname }} {{ $remittance->member->lname }}</strong>
                                                                <br>
                                                                <span class="member-id">ID: {{ $remittance->member->emp_id }}</span>
                                                            </div>
                                                        @else
                                                            <span class="badge badge-danger">Member not found</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($remittance->branch)
                                                            <span class="badge badge-info badge-status">
                                                                {{ $remittance->branch->name }}
                                                            </span>
                                                        @else
                                                            <span class="badge badge-danger badge-status">Branch not found</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-right amount-col">₱{{ number_format($remittance->loan_payment, 2) }}</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="viewDetails({{ $remittance->id }})">
                                                            <i class="fa fa-eye"></i> View
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card table-card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h4 class="card-title mb-0">
                                        <i class="fa fa-piggy-bank text-success"></i> Savings Accounts
                                    </h4>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="savingsTable" class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Account Number</th>
                                                <th>Member</th>
                                                <th>Product</th>
                                                <th class="text-right">Current Balance</th>
                                                <th>Last Updated</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($savings as $account)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $account->account_number }}</strong>
                                                    </td>
                                                    <td>
                                                        @if($account->member)
                                                            <div class="member-info">
                                                                <strong>{{ $account->member->fname }} {{ $account->member->lname }}</strong>
                                                                <br>
                                                                <span class="member-id">ID: {{ $account->member->emp_id }}</span>
                                                            </div>
                                                        @else
                                                            <span class="badge badge-danger">Member not found</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info badge-status">
                                                            {{ $account->product_name }}
                                                        </span>
                                                    </td>
                                                    <td class="text-right amount-col">₱{{ number_format($account->current_balance, 2) }}</td>
                                                    <td>
                                                        <strong>{{ $account->updated_at->format('M d, Y') }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $account->updated_at->format('h:i A') }}</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-{{ $account->account_status === 'deduction' ? 'success' : 'warning' }} badge-status">
                                                            {{ ucfirst($account->account_status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="copyright">
                <p>Copyright © Designed &amp; Developed by <a href="https://mass-specc.coop/" target="_blank">MASS-SPECC COOPERATIVE</a>2025</p>
            </div>
        </div>
    </div>

    @include('layouts.partials.footer')

    <script>
        $(document).ready(function() {
            $('#remittanceTable').DataTable({
                pageLength: 10,
                ordering: true,
                order: [[0, 'desc']],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                responsive: true,
                language: {
                    search: '<i class="fa fa-search"></i>',
                    searchPlaceholder: 'Search records'
                }
            });

            $('#savingsTable').DataTable({
                pageLength: 10,
                ordering: true,
                order: [[4, 'desc']],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                responsive: true,
                language: {
                    search: '<i class="fa fa-search"></i>',
                    searchPlaceholder: 'Search accounts'
                }
            });
        });

        function viewDetails(id) {
            // Add your view details logic here
            alert('View details for remittance ID: ' + id);
        }
    </script>
</body>
</html>
