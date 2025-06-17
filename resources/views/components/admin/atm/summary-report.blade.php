<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Summary Report - ATM Management</title>

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
                            <h4>Summary Report</h4>
                            <span class="ml-1">Account Balances Overview</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.atm') }}">ATM Management</a></li>
                            <li class="breadcrumb-item active">Summary Report</li>
                        </ol>
                    </div>
                </div>

                <!-- Overall Summary -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Overall Summary</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Total Savings</th>
                                                <th>Total Shares</th>
                                                <th>Total Loans</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>₱{{ number_format($summary->total_savings, 2) }}</td>
                                                <td>₱{{ number_format($summary->total_shares, 2) }}</td>
                                                <td>₱{{ number_format($summary->total_loans, 2) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Branch-wise Summary -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Branch-wise Summary</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Branch</th>
                                                <th>Total Savings</th>
                                                <th>Total Shares</th>
                                                <th>Total Loans</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($branchSummary as $branch)
                                            <tr>
                                                <td>{{ $branch->branch_name }}</td>
                                                <td>₱{{ number_format($branch->total_savings, 2) }}</td>
                                                <td>₱{{ number_format($branch->total_shares, 2) }}</td>
                                                <td>₱{{ number_format($branch->total_loans, 2) }}</td>
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
                <p>Copyright © Designed &amp; Developed by <a href="https://mass-specc.coop/" target="_blank">MASS-SPECC COOPERATIVE</a> 2025</p>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('js/quixnav-init.js') }}"></script>
    <script src="{{ asset('js/custom.min.js') }}"></script>
</body>
</html>
