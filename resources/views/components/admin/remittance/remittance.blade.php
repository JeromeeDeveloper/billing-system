<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Remittance Upload - Billing and Collection</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">
    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        .stats-card {
            transition: transform 0.2s;
            cursor: pointer;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .preview-table th {
            background-color: #f3f6f9;
        }

        .upload-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        h5.text-center {
            padding: 18px;
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
                            <h4>Remittance Upload</h4>
                            <span class="ml-1">Upload and Process Remittance Data</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Remittance Upload</li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center title-container">
                                    <h4 class="card-title mb-0">Upload Remittance Excel File</h4>
                                </div>
                                <div class="d-flex align-items-center ms-3">

                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Information Note -->
                                <div class="alert alert-info alert-dismissible fade show mb-4">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <h5><i class="fa fa-info-circle"></i> Remittance Flow & User Guide (Admin)</h5>
                                    <ol class="mb-2">
                                        <li><strong>Upload:</strong> Admin uploads remittance files for loans/savings
                                            and shares.</li>
                                        <li><strong>Processing:</strong> System matches and processes payments based on
                                            prioritization and member data.</li>
                                        <li><strong>Preview & Export:</strong> Admin can preview, process, and export
                                            remittance data for all branches.</li>
                                        <li><strong>Branch Filtering:</strong> Remittance data is automatically filtered
                                            for each branch user.</li>
                                    </ol>
                                    <ul class="mb-2">
                                        <li><strong>File Requirements:</strong> Ensure files meet the required format
                                            and headers before uploading.</li>
                                        <li><strong>History:</strong> View and download previous remittance uploads and
                                            exports.</li>
                                    </ul>
                                    <p class="mb-0"><small><strong>Note:</strong> Only admin can upload remittance
                                            data. Branch users can only export and view data filtered to their
                                            branch.</small></p>
                                </div>

                                <!-- Alerts Section (Success, Error, Billing Period) -->
                                <div class="row g-3 mb-4">
                                    <div class="col-12 col-md-6">
                                        @if (session('success'))
                                            <div class="alert alert-success alert-dismissible fade show">
                                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                                <strong><i class="fa fa-check-circle"></i> Success!</strong> {{ session('success') }}
                                            </div>
                                        @endif
                                        @if (session('error'))
                                            <div class="alert alert-danger alert-dismissible fade show">
                                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                                <strong><i class="fa fa-exclamation-circle"></i> Error!</strong> {{ session('error') }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-12 col-md-12">
                                        <div class="alert alert-warning alert-dismissible fade show mb-0">
                                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                                            <h6 class="mb-1"><i class="fa fa-calendar"></i> Current Billing Period</h6>
                                            <p class="mb-0">
                                                <strong>Period:</strong>
                                                @php
                                                    $billingPeriod = auth()->user()->billing_period ? \Carbon\Carbon::parse(auth()->user()->billing_period)->format('F Y') : 'Not Set';
                                                @endphp
                                                {{ $billingPeriod }}
                                            </p>
                                            <p class="mb-0 small text-muted">
                                                <i class="fa fa-info-circle"></i> Remittance data will be filtered and exported only for this billing period.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modernized Remittance Upload Section (Installment Forecast on top, others below) -->
                                <div class="row g-4 mb-4">
                                    <div class="col-12 mb-3">
                                        <div class="card shadow-sm h-100">
                                            <div class="card-header bg-primary text-white">
                                                <i class="fa fa-upload me-2"></i>Installment Forecast Upload
                                            </div>
                                            <div class="card-body">
                                                <form action="{{ route('document.upload') }}" method="POST" enctype="multipart/form-data" id="installmentForm">
                                                    @csrf
                                                    <div class="mb-3">
                                                        <label for="remit_installment_file" class="form-label">Select File</label>
                                                        <input type="file" class="form-control" id="remit_installment_file" name="file">
                                                    </div>
                                                    <button type="submit" class="btn btn-primary w-100">
                                                        <i class="fa fa-upload me-1"></i> Upload Installment File
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="card shadow-sm h-100">
                                            <div class="card-header bg-success text-white">
                                                <i class="fa fa-upload me-2"></i>Savings & Loans Remittance Upload
                                            </div>
                                            <div class="card-body">
                                                <form action="{{ route('remittance.upload') }}" method="POST" enctype="multipart/form-data" id="loansSavingsForm">
                                                    @csrf
                                                    <div class="mb-3">
                                                        <label class="form-label">Select File</label>
                                                        <input type="file" class="form-control" name="file" id="file" accept=".xlsx,.xls,.csv" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <small class="text-muted">Excel format (.xlsx, .xls, .csv). Required headers: CID, Name, Loans, Savings Product Names.</small>
                                                    </div>

                                                    <button type="submit" class="btn btn-success btn-block mt-2">
                                                            <i class="fa fa-upload me-1"></i> Upload and Process Loans & Savings Remittance
                                                        </button>

                                                    <button type="button" class="btn btn-warning btn-block mt-2" data-toggle="modal" data-target="#loansSavingsFormatModal">
                                                            <i class="fa fa-eye me-1"></i> View Expected Format
                                                    </button>

                                                    <a href="javascript:void(0);" class="btn btn-primary btn-block mt-2" onclick="generateExport('loans_savings')">
                                                        Collection file for Loans & Savings
                                                    </a>
                                                    <a href="javascript:void(0);" class="btn btn-info btn-block mt-2" onclick="generateExport('loans_savings_with_product')">
                                                        Collection file for Loans & Savings (with Product Name)
                                                    </a>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="card shadow-sm h-100">
                                            <div class="card-header bg-info text-white">
                                                <i class="fa fa-upload me-2"></i>Share Remittance Upload
                                            </div>
                                            <div class="card-body">
                                                <form action="{{ route('remittance.upload.share') }}" method="POST" enctype="multipart/form-data" id="shareForm">
                                                    @csrf
                                                    <div class="mb-3">
                                                        <label class="form-label">Select File</label>
                                                        <input type="file" class="form-control" name="file" id="shareFile" accept=".xlsx,.xls,.csv" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <small class="text-muted">Excel format (.xlsx, .xls, .csv). Required headers: CID, Name (LASTNAME, FIRSTNAME), Share (amount).</small>
                                                    </div>

                                                        <button type="submit" class="btn btn-success btn-block mt-2">
                                                            <i class="fa fa-upload me-1"></i> Upload and Process Share Remittance
                                                        </button>
                                                        <button type="button" class="btn btn-warning btn-block mt-2" data-toggle="modal" data-target="#sharesFormatModal">
                                                            <i class="fa fa-eye me-1"></i> View Expected Format
                                                        </button>

                                                    <a href="javascript:void(0);" class="btn btn-primary btn-block mt-2" onclick="generateExport('shares')">
                                                        Collection file for Shares
                                                    </a>
                                                    <a href="javascript:void(0);" class="btn btn-info btn-block mt-2" onclick="generateExport('shares_with_product')">
                                                        Collection file for Shares (with Product Name)
                                                    </a>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Remittance Preview Section (unchanged, just add card and spacing) -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-body">
                                        {{-- Loans & Savings Remittance Preview --}}
                                        <div class="mb-4">
                                            <h5 class="text-center">Loans & Savings Remittance Preview</h5>
                                            <div class="row mb-3 justify-content-between align-items-center">
                                                <div class="col-12 col-md-7 mb-2 mb-md-0">
                                                    <div class="btn-group" role="group">
                                                        <!-- Filter buttons (unchanged) -->
                                                        @php $filter = request('loans_filter'); @endphp
                                                        <a href="{{ route('remittance.index') }}" class="btn {{ !$filter ? 'btn-primary' : 'btn-outline-primary' }} mr-2">All Records</a>
                                                        <a href="{{ route('remittance.index', array_merge(request()->except('loans_page'), ['loans_filter' => 'matched'])) }}" class="btn {{ $filter === 'matched' ? 'btn-success' : 'btn-outline-success' }} mr-2">Matched Only</a>
                                                        <a href="{{ route('remittance.index', array_merge(request()->except('loans_page'), ['loans_filter' => 'unmatched'])) }}" class="btn {{ $filter === 'unmatched' ? 'btn-danger' : 'btn-outline-danger' }} mr-2">Unmatched Only</a>
                                                        <a href="{{ route('remittance.index', array_merge(request()->except('loans_page'), ['loans_filter' => 'no_branch'])) }}" class="btn {{ $filter === 'no_branch' ? 'btn-info' : 'btn-outline-info' }}">No Branch</a>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-md-5 d-flex justify-content-md-end">
                                                    <form method="GET" class="form-inline w-100 justify-content-end">
                                                        @if (request()->has('loans_filter'))
                                                            <input type="hidden" name="loans_filter" value="{{ request('loans_filter') }}">
                                                        @endif
                                                        <input type="text" name="loans_search" value="{{ request('loans_search') }}" class="form-control mr-2 w-auto" placeholder="Search by name or emp_id">
                                                        <button type="submit" class="btn btn-outline-secondary btn-sm mr-2">Search</button>
                                                        @if (request()->has('loans_search') || request()->has('loans_filter'))
                                                            <a href="{{ route('remittance.index') }}" class="btn btn-outline-warning btn-sm">Clear</a>
                                                        @endif
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered text-center">
                                                    <thead>
                                                        <tr>
                                                            <th>Member</th>
                                                            <th>Loans</th>
                                                            <th>Savings</th>
                                                            <th>Status</th>
                                                            <th>Message</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($loansSavingsPreviewPaginated as $row)
                                                            <tr>
                                                                <td>{{ $row->name }}</td>
                                                                <td>{{ $row->loans }}</td>
                                                                <td>{{ is_array($row->savings) ? $row->savings['total'] ?? 0 : $row->savings }}</td>
                                                                <td>{{ $row->status }}</td>
                                                                <td>{{ $row->message }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="text-center text-muted">No records found.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 d-flex justify-content-center text-center">
                                                    {{ $loansSavingsPreviewPaginated->appends(request()->except('loans_page'))->links() }}
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Shares Remittance Preview --}}
                                        <div class="mb-4">
                                            <h5 class="text-center">Shares Remittance Preview</h5>
                                            <div class="row mb-3 justify-content-between align-items-center">
                                                <div class="col-12 col-md-7 mb-2 mb-md-0">
                                                    <div class="btn-group" role="group">
                                                        <!-- Filter buttons (unchanged) -->
                                                        @php $filter = request('shares_filter'); @endphp
                                                        <a href="{{ route('remittance.index') }}" class="btn {{ !$filter ? 'btn-primary' : 'btn-outline-primary' }} mr-2">All Records</a>
                                                        <a href="{{ route('remittance.index', array_merge(request()->except('shares_page'), ['shares_filter' => 'matched'])) }}" class="btn {{ $filter === 'matched' ? 'btn-success' : 'btn-outline-success' }} mr-2">Matched Only</a>
                                                        <a href="{{ route('remittance.index', array_merge(request()->except('shares_page'), ['shares_filter' => 'unmatched'])) }}" class="btn {{ $filter === 'unmatched' ? 'btn-danger' : 'btn-outline-danger' }} mr-2">Unmatched Only</a>
                                                        <a href="{{ route('remittance.index', array_merge(request()->except('shares_page'), ['shares_filter' => 'no_branch'])) }}" class="btn {{ $filter === 'no_branch' ? 'btn-info' : 'btn-outline-info' }}">No Branch</a>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-md-5 d-flex justify-content-md-end">
                                                    <form method="GET" class="form-inline w-100 justify-content-end">
                                                        @if (request()->has('shares_filter'))
                                                            <input type="hidden" name="shares_filter" value="{{ request('shares_filter') }}">
                                                        @endif
                                                        <input type="text" name="shares_search" value="{{ request('shares_search') }}" class="form-control mr-2 w-auto" placeholder="Search by name or emp_id">
                                                        <button type="submit" class="btn btn-outline-secondary btn-sm mr-2">Search</button>
                                                        @if (request()->has('shares_search') || request()->has('shares_filter'))
                                                            <a href="{{ route('remittance.index') }}" class="btn btn-outline-warning btn-sm">Clear</a>
                                                        @endif
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered text-center">
                                                    <thead>
                                                        <tr>
                                                            <th>Member</th>
                                                            <th>Shares</th>
                                                            <th>Status</th>
                                                            <th>Message</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($sharesPreviewPaginated as $row)
                                                            <tr>
                                                                <td>{{ $row->name }}</td>
                                                                <td>{{ $row->share_amount }}</td>
                                                                <td>{{ $row->status }}</td>
                                                                <td>{{ $row->message }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="4" class="text-center text-muted">No records found.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 d-flex justify-content-center text-center">
                                                    {{ $sharesPreviewPaginated->appends(request()->except('shares_page'))->links() }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            @if (isset($comparisonReportPaginated) && $comparisonReportPaginated->count() > 0)

            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Billed vs Remitted Comparison Report</h5>
                        <a href="{{ route('remittance.exportComparison') }}" class="btn btn-success">
                            <i class="fa fa-file-excel-o"></i> Export Billed vs Remitted Comparison to Excel
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>CID</th>
                                        <th>Member Name</th>
                                        <th>Total Billed</th>
                                        <th>Remitted Loans</th>
                                        <th>Remaining Loans</th>
                                        <th>Remitted Savings</th>
                                        <th>Remitted Shares</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($comparisonReportPaginated as $row)
                                        <tr>
                                            <td>{{ $row['cid'] }}</td>
                                            <td>{{ $row['member_name'] }}</td>
                                            <td>₱{{ $row['loan_balance'] }}</td>
                                            <td>₱{{ number_format($row['remitted_loans'], 2) }}</td>
                                            <td>₱{{ number_format($row['remaining_loan_balance'], 2) }}</td>
                                            <td>₱{{ number_format($row['remitted_savings'], 2) }}</td>
                                            <td>₱{{ number_format($row['remitted_shares'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-center mt-2 text-center">
                                {{ $comparisonReportPaginated->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>


    @endif
</div>
        </div>
    </div>

    <style>
        .flex.justify-between.flex-1.sm\:hidden {
            display: none;
        }
    </style>

    <div class="footer">
        <div class="copyright">
            <p>Copyright © Designed &amp; Developed by <a href="https://mass-specc.coop/" target="_blank">MASS-SPECC
                    COOPERATIVE</a>2025</p>
        </div>
    </div>
    </div>

    @include('layouts.partials.footer')

    <!-- Loans & Savings Format Modal -->
    <div class="modal fade" id="loansSavingsFormatModal" tabindex="-1" role="dialog"
        aria-labelledby="loansSavingsFormatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loansSavingsFormatModalLabel">
                        <i class="fa fa-file-excel text-success"></i> Loans & Savings Remittance Format
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h6><i class="fa fa-info-circle"></i> File Format Requirements:</h6>
                        <ul class="mb-0">
                            <li><strong>File Type:</strong> Excel (.xlsx, .xls) or CSV</li>
                            <li><strong>First Row:</strong> Must contain headers exactly as shown below</li>
                            <li><strong>Data Rows:</strong> Start from row 2 onwards</li>
                            <li><strong>Amounts:</strong> Use numbers only (no currency symbols)</li>
                        </ul>
                    </div>

                    <h6 class="font-weight-bold mb-3">Required Headers (First Row):</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Header Name</th>
                                    <th>Description</th>
                                    <th>Required</th>
                                    <th>Example</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>cid</code></td>
                                    <td>CID</td>
                                    <td><span class="badge badge-success">Yes</span></td>
                                    <td>000000123</td>
                                </tr>
                                <tr>
                                    <td><code>name</code></td>
                                    <td>Name</td>
                                    <td><span class="badge badge-success">No</span></td>
                                    <td>John</td>
                                </tr>
                                <tr>
                                    <td><code>loans</code></td>
                                    <td>Total Loan Payment Amount</td>
                                    <td><span class="badge badge-success">No</span></td>
                                    <td>1500.00</td>
                                </tr>
                                <tr>
                                    <td><code>savings</code></td>
                                    <td>Total Savings Amount (will be distributed automatically based on
                                        deduction_amount)</td>
                                    <td><span class="badge badge-warning">Optional</span></td>
                                    <td>1000.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="font-weight-bold mb-3 mt-4">Sample Data:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>cid</th>
                                    <th>name</th>
                                    <th>loans</th>
                                    <th>savings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>000000123</td>
                                    <td>John</td>
                                    <td>1500.00</td>
                                    <td>1000.00</td>
                                </tr>
                                <tr>
                                    <td>000000001</td>
                                    <td>Jane</td>
                                    <td>2000.00</td>
                                    <td>750.00</td>
                                </tr>
                                <tr>
                                    <td>000000002</td>
                                    <td>Bob</td>
                                    <td>1200.00</td>
                                    <td>0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <h6><i class="fa fa-info-circle"></i> Important Notes:</h6>
                        <ul class="mb-0">
                            <li><strong>Savings Distribution:</strong> The total savings amount will be automatically
                                distributed based on each member's <code>deduction_amount</code> settings</li>
                            <li><strong>Prioritization:</strong> Distribution follows the product prioritization order
                            </li>
                            <li><strong>Remaining Amount:</strong> Any remaining amount after distribution goes to
                                Regular Savings</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Shares Format Modal -->
    <div class="modal fade" id="sharesFormatModal" tabindex="-1" role="dialog"
        aria-labelledby="sharesFormatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sharesFormatModalLabel">
                        <i class="fa fa-file-excel text-success"></i> Shares Remittance Format
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h6><i class="fa fa-info-circle"></i> File Format Requirements:</h6>
                        <ul class="mb-0">
                            <li><strong>File Type:</strong> Excel (.xlsx, .xls) or CSV</li>
                            <li><strong>First Row:</strong> Must contain headers exactly as shown below</li>
                            <li><strong>Data Rows:</strong> Start from row 2 onwards</li>
                            <li><strong>Name Format:</strong> LASTNAME, FIRSTNAME (comma separated)</li>
                            <li><strong>Amounts:</strong> Use numbers only (no currency symbols)</li>
                        </ul>
                    </div>

                    <h6 class="font-weight-bold mb-3">Required Headers (First Row):</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Header Name</th>
                                    <th>Description</th>
                                    <th>Required</th>
                                    <th>Example</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>CID</code></td>
                                    <td>Member CID</td>
                                    <td><span class="badge badge-success">Yes</span></td>
                                    <td>000000123</td>
                                </tr>
                                <tr>
                                    <td><code>Name</code></td>
                                    <td>Name (optional)</td>
                                    <td><span class="badge badge-warning">No</span></td>
                                    <td>John</td>
                                </tr>
                                <tr>
                                    <td><code>Share</code></td>
                                    <td>Share Capital Amount</td>
                                    <td><span class="badge badge-success">Yes</span></td>
                                    <td>1000.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="font-weight-bold mb-3 mt-4">Sample Data:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>CID</th>
                                    <th>Name</th>
                                    <th>Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>000000123</td>
                                    <td>JOHN</td>
                                    <td>1000.00</td>
                                </tr>
                                <tr>
                                    <td>000000001</td>
                                    <td>JANE</td>
                                    <td>1500.00</td>
                                </tr>
                                <tr>
                                    <td>000000002</td>
                                    <td>JOHNSON</td>
                                    <td>750.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <h6><i class="fa fa-exclamation-triangle"></i> Important Notes:</h6>
                        <ul class="mb-0">
                            <li><strong>Name Format:</strong> Must be "LASTNAME FIRSTNAME" (optional)</li>
                            <li><strong>CID:</strong> CID is required</li>
                            <li><strong>Share Amount:</strong> Must be a positive number</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

                </div>
            </div>
        </div>
    </div>

    <!-- Forecast Upload Guide Pop-up -->
    <div id="forecastGuidePopup" class="position-fixed" style="bottom: 24px; right: 24px; z-index: 1055; min-width: 320px; max-width: 90vw;">
        <div class="alert alert-info alert-dismissible fade show shadow" role="alert">
            <strong><i class="fa fa-info-circle"></i> Reminder:</strong> Please upload the <b>Installment Forecast</b> first before uploading any remittance files.<br>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // // Update custom file input label
        // $('.custom-file-input').on('change', function() {
        //     let fileName = $(this).val().split('\\').pop();
        //     $(this).next('.custom-file-label').addClass("selected").html(fileName);
        // });

        // // Initialize DataTable if preview exists
        // $(document).ready(function() {
        //     if ($('.table').length) {
        //         $('.table').DataTable({
        //             pageLength: 25,
        //             ordering: true,
        //             dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
        //                 '<"row"<"col-sm-12"tr>>' +
        //                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        //             buttons: ['copy', 'excel', 'pdf', 'print']
        //         });
        //     }

        //     // Auto-hide alerts after 5 seconds
        //     setTimeout(function() {
        //         $('.alert').alert('close');
        //     }, 5000);
        // });

        function generateExport(type) {
            let url = '{{ route('remittance.generateExport') }}';
            window.location.href = url + '?type=' + type;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Installment form
            const installmentForm = document.getElementById('installmentForm');
            if (installmentForm) {
                installmentForm.addEventListener('submit', function(e) {
                    const submitBtn = document.getElementById('installmentSubmitBtn');
                    const originalText = submitBtn.innerHTML;

                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Uploading...';

                    Swal.fire({
                        title: 'Uploading Installment File...',
                        html: 'Please wait while we process your installment forecast file. This may take a few moments.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                });
            }

            // Loans and Savings form
            const loansSavingsForm = document.getElementById('loansSavingsForm');
            if (loansSavingsForm) {
                loansSavingsForm.addEventListener('submit', function(e) {
                    const submitBtn = document.getElementById('loansSavingsSubmitBtn');
                    const originalText = submitBtn.innerHTML;

                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

                    Swal.fire({
                        title: 'Processing Loans & Savings Remittance...',
                        html: 'Please wait while we match and process your remittance data. This may take a few moments.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                });
            }

            // Share form
            const shareForm = document.getElementById('shareForm');
            if (shareForm) {
                shareForm.addEventListener('submit', function(e) {
                    const submitBtn = document.getElementById('shareSubmitBtn');
                    const originalText = submitBtn.innerHTML;

                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

                    Swal.fire({
                        title: 'Processing Share Remittance...',
                        html: 'Please wait while we match and process your share remittance data. This may take a few moments.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                });
            }

            // File input change handlers
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName);

                // Show file info
                if (fileName) {
                    const file = this.files[0];
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);

                    Swal.fire({
                        icon: 'info',
                        title: 'File Selected',
                        html: `
                            <p><strong>File:</strong> ${fileName}</p>
                            <p><strong>Size:</strong> ${fileSize} MB</p>
                            <p><strong>Type:</strong> ${file.type || 'Unknown'}</p>
                        `,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        });
    </script>
</body>

</html>
