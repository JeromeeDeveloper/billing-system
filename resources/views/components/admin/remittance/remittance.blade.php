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
                                    <h4 class="card-title mb-0">Remittance</h4>
                                </div>
                                <div class="d-flex align-items-center ms-3">

                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Information Note -->
                                <div class="row mb-4">
                                    <div class="col-12 mb-3">
                                        <div class="alert alert-info">
                                            <h6 class="alert-heading"><i class="fa fa-info-circle"></i> Remittance Upload Information</h6>
                                            <p class="mb-2"><strong>Current Billing Period:</strong> {{ \Carbon\Carbon::parse(Auth::user()->billing_period)->format('Y F') }}</p>

                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card stats-card bg-light border-primary">
                                            <div class="card-body text-center">
                                                <h5 class="card-title text-primary mb-2">
                                                    <i class="fa fa-upload"></i> Remittance (Regular)
                                                </h5>
                                                <h2 class="mb-0">{{ $remittanceImportRegularCount / 2 }}</h2>
                                                @php
                                                    $regularCount = $remittanceImportRegularCount / 2;
                                                    $ordinal = '';
                                                    if ($regularCount == 1) $ordinal = '1st';
                                                    elseif ($regularCount == 2) $ordinal = '2nd';
                                                    elseif ($regularCount == 3) $ordinal = '3rd';
                                                    else $ordinal = $regularCount . 'th';
                                                @endphp
                                                <small class="text-muted">{{ $ordinal }} remittance uploaded this period</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card stats-card bg-light border-warning">
                                            <div class="card-body text-center">
                                                <h5 class="card-title text-warning mb-2">
                                                    <i class="fa fa-upload"></i> Remittance (Special)
                                                </h5>
                                                <h2 class="mb-0">{{ $remittanceImportSpecialCount / 2 }}</h2>
                                                @php
                                                    $specialCount = $remittanceImportSpecialCount / 2;
                                                    $ordinal = '';
                                                    if ($specialCount == 1) $ordinal = '1st';
                                                    elseif ($specialCount == 2) $ordinal = '2nd';
                                                    elseif ($specialCount == 3) $ordinal = '3rd';
                                                    else $ordinal = $specialCount . 'th';
                                                @endphp
                                                <small class="text-muted">{{ $ordinal }} remittance uploaded this period</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card stats-card bg-light border-info">
                                            <div class="card-body text-center">
                                                <h5 class="card-title text-info mb-2">
                                                    <i class="fa fa-upload"></i> Remittance (Shares)
                                                </h5>
                                                <h2 class="mb-0">{{ $sharesRemittanceImportCount}}</h2>
                                                {{-- @php
                                                    $sharesCount = $sharesRemittanceImportCount / 2;
                                                    $ordinal = '';
                                                    if ($sharesCount == 1) $ordinal = '1st';
                                                    elseif ($sharesCount == 2) $ordinal = '2nd';
                                                    elseif ($sharesCount == 3) $ordinal = '3rd';
                                                    else $ordinal = $sharesCount . 'th';
                                                @endphp --}}
                                                <small class="text-muted">{{ $sharesRemittanceImportCount }} remittance uploaded this period</small>
                                            </div>
                                        </div>
                                    </div>
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
                                                    <button type="submit" class="btn btn-primary w-100" id="installmentSubmitBtn">
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
                                                    <input type="hidden" name="billing_type" id="billingTypeInput" value="regular">
                                                    <div class="mb-3">
                                                        <small class="text-muted">Excel format (.xlsx, .xls, .csv). Required headers: CID, Name, Loans, Savings Product Names.</small>
                                                    </div>
                                                    <button type="button" class="btn btn-success btn-block mt-2" id="showBillingTypeModalBtn">
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

                                                        <button type="submit" class="btn btn-success btn-block mt-2" id="shareSubmitBtn">
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

                {{-- Export Button for Regular & Special Billing Remittance --}}
                <div class="mb-3 text-right">
                    <a href="{{ route('remittance.exportRegularSpecial') }}" class="btn btn-success">
                        <i class="fa fa-file-excel-o"></i> Export Regular & Special Billing Remittance
                    </a>
                </div>

            @if (isset($regularRemittances) && $regularRemittances->count() > 0)
                @include('components.admin.remittance.billing_table', [
                    'remittances' => $regularRemittances,
                    'billed' => $regularBilled,
                    'type' => 'Regular'
                ])
            @endif
            @if (isset($specialRemittances) && $specialRemittances->count() > 0)
                @include('components.admin.remittance.billing_table', [
                    'remittances' => $specialRemittances,
                    'billed' => $specialBilled,
                    'type' => 'Special'
                ])
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

    <!-- Billing Type Modal -->
    <div class="modal fade" id="billingTypeModal" tabindex="-1" role="dialog" aria-labelledby="billingTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="billingTypeModalLabel">Select Billing Type</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><strong>Choose which billing type to process for this upload:</strong></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="billing_type_modal" id="billingTypeRegular" value="regular" checked>
                            <label class="form-check-label" for="billingTypeRegular">Regular</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="billing_type_modal" id="billingTypeSpecial" value="special">
                            <label class="form-check-label" for="billingTypeSpecial">Special</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmBillingTypeBtn">Proceed with Upload</button>
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

            // Show modal on upload button click
            document.getElementById('showBillingTypeModalBtn').addEventListener('click', function(e) {
                $('#billingTypeModal').modal('show');
            });
            // On confirm, set hidden input and submit form
            document.getElementById('confirmBillingTypeBtn').addEventListener('click', function(e) {
                var selectedType = document.querySelector('input[name="billing_type_modal"]:checked').value;
                document.getElementById('billingTypeInput').value = selectedType;

                // Show loading state
                const confirmBtn = document.getElementById('confirmBillingTypeBtn');
                const originalText = confirmBtn.innerHTML;

                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

                // Close modal
                $('#billingTypeModal').modal('hide');

                // Show SweetAlert loading
                Swal.fire({
                    title: 'Processing Remittance Upload...',
                    html: 'Please wait while we match and process your remittance data. This may take a few moments.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Submit the form
                document.getElementById('loansSavingsForm').submit();
            });
        });
    </script>
</body>

</html>
