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
                            <li class="breadcrumb-item"><a href="{{ route('dashboard_branch') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Remittance Upload</li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            {{-- <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center title-container">
                                    <h4 class="card-title mb-0">Remittance Collection Files (Branch)</h4>
                                </div>
                                <div class="d-flex align-items-center ms-3">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-file-excel"></i> Export Collection Files
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('branch.remittance.generateExport', ['type' => 'loans_savings']) }}">Loans & Savings</a>
                                            <a class="dropdown-item" href="{{ route('branch.remittance.generateExport', ['type' => 'shares']) }}">Shares</a>
                                        </div>
                                    </div>
                                </div>
                            </div> --}}
                            <div class="card-body">
                                <!-- Information Note -->
                                <div class="alert alert-info alert-dismissible show mb-4">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <h5><i class="fa fa-info-circle"></i> Branch Remittance Flow & User Guide</h5>
                                    <ol class="mb-2">
                                        <li><strong>Export Only:</strong> Branch users can only export collection files for loans/savings and shares for their branch.</li>
                                        <li><strong>Preview:</strong> Preview remittance data filtered to your branch before exporting.</li>
                                        <li><strong>Format:</strong> Collection files use the same format and logic as admin exports.</li>
                                    </ol>
                                    <ul class="mb-2">
                                        <li><strong>Branch-Specific:</strong> All data and exports are limited to your branch's members.</li>
                                        <li><strong>History:</strong> View and download previous remittance exports for your branch.</li>
                                    </ul>
                                    <p class="mb-0"><small><strong>Note:</strong> Branch users cannot upload remittance data. All exports are restricted to your branch's members only.</small></p>
                                </div>

                                @if (session('success'))
                                    <div class="alert alert-success alert-dismissible fade show">
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        <strong><i class="fa fa-check-circle"></i> Success!</strong>
                                        {{ session('success') }}
                                    </div>
                                @endif

                                @if (session('error'))
                                    <div class="alert alert-danger alert-dismissible show">
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        <strong><i class="fa fa-exclamation-circle"></i> Error!</strong>
                                        {{ session('error') }}
                                    </div>
                                @endif

                                <!-- Current Billing Period Display -->
                                <div class="alert alert-warning alert-dismissible show mb-4">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <h6><i class="fa fa-calendar"></i> Current Billing Period</h6>
                                    <p class="mb-0">
                                        <strong>Period:</strong>
                                        @php
                                            $billingPeriod = auth()->user()->billing_period
                                                ? \Carbon\Carbon::parse(auth()->user()->billing_period)->format('F Y')
                                                : 'Not Set';
                                        @endphp
                                        {{ $billingPeriod }}
                                    </p>
                                    <p class="mb-0 small text-muted">
                                        <i class="fa fa-info-circle"></i>
                                        Remittance data will be filtered and exported only for this billing period.
                                    </p>
                                </div>

                                <!-- Export Section -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card border-primary">
                                            <div class="card-header bg-primary text-white">
                                                <h6 class="mb-0"><i class="fa fa-file-excel"></i> Loans & Savings Collection</h6>
                                            </div>
                                            <div class="card-body">
                                                <p class="text-muted small">Generate collection file for loans and savings remittance data.</p>
                                                <a href="{{ route('branch.remittance.generateExport', ['type' => 'loans_savings']) }}" class="btn btn-primary btn-block">
                                                    <i class="fa fa-download"></i> Export Loans & Savings
                                                </a>
                                                <a href="{{ route('branch.remittance.generateExport', ['type' => 'loans_savings_with_product']) }}" class="btn btn-outline-primary btn-block mt-2">
                                                    <i class="fa fa-download"></i> Export Loans & Savings (with Product Name)
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-success">
                                            <div class="card-header bg-success text-white">
                                                <h6 class="mb-0"><i class="fa fa-file-excel"></i> Shares Collection</h6>
                                            </div>
                                            <div class="card-body">
                                                <p class="text-muted small">Generate collection file for shares remittance data.</p>
                                                <a href="{{ route('branch.remittance.generateExport', ['type' => 'shares']) }}" class="btn btn-success btn-block">
                                                    <i class="fa fa-download"></i> Export Shares
                                                </a>
                                                <a href="{{ route('branch.remittance.generateExport', ['type' => 'shares_with_product']) }}" class="btn btn-outline-success btn-block mt-2">
                                                    <i class="fa fa-download"></i> Export Shares (with Product Name)
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Preview Section -->
                                <div class="row">
                                    <div class="col-12">
                                        @if (isset($preview) && $preview)
                                            <div class="row mb-4">
                                                <div class="col-md-4">
                                                    <a href="{{ route('branch.remittance.index', ['filter' => 'matched']) }}"
                                                        class="text-decoration-none">
                                                        <div class="card stats-card bg-success-light">
                                                            <div class="card-body">
                                                                <div class="media align-items-center">
                                                                    <div class="media-body mr-3">
                                                                        <h2 class="text-success">{{ $stats['matched'] ?? 0 }}</h2>
                                                                        <span class="text-success">Matched Records</span>
                                                                    </div>
                                                                    <i class="fa fa-check-circle fa-3x text-success"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </div>
                                                <div class="col-md-4">
                                                    <a href="{{ route('branch.remittance.index', ['filter' => 'unmatched']) }}"
                                                        class="text-decoration-none">
                                                        <div class="card stats-card bg-danger-light">
                                                            <div class="card-body">
                                                                <div class="media align-items-center">
                                                                    <div class="media-body mr-3">
                                                                        <h2 class="text-danger">{{ $stats['unmatched'] ?? 0 }}</h2>
                                                                        <span class="text-danger">Unmatched Records</span>
                                                                    </div>
                                                                    <i class="fa fa-exclamation-circle fa-3x text-danger"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card stats-card bg-info-light">
                                                        <div class="card-body">
                                                            <div class="media align-items-center">
                                                                <div class="media-body mr-3">
                                                                    <h2 class="text-info">₱{{ number_format($stats['total_amount'] ?? 0, 2) }}</h2>
                                                                    <span class="text-info">Total Amount</span>
                                                                </div>
                                                                <i class="fa fa-money-bill fa-3x text-info"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div>
                                                    <a href="{{ route('branch.remittance.index') }}"
                                                        class="btn {{ !request()->has('filter') ? 'btn-primary' : 'btn-outline-primary' }}">
                                                        All Records
                                                    </a>
                                                    <a href="{{ route('branch.remittance.index', ['filter' => 'matched']) }}"
                                                        class="btn {{ request()->get('filter') === 'matched' ? 'btn-success' : 'btn-outline-success' }}">
                                                        Matched Only
                                                    </a>
                                                    <a href="{{ route('branch.remittance.index', ['filter' => 'unmatched']) }}"
                                                        class="btn {{ request()->get('filter') === 'unmatched' ? 'btn-danger' : 'btn-outline-danger' }}">
                                                        Unmatched Only
                                                    </a>
                                                </div>
                                                <div>
                                                    <form method="GET" class="form-inline">
                                                        @if(request()->has('filter'))
                                                            <input type="hidden" name="filter" value="{{ request('filter') }}">
                                                        @endif
                                                        <input type="text" name="search" value="{{ request('search') }}" class="form-control mr-2" placeholder="Search by name or emp_id">
                                                        <button type="submit" class="btn btn-outline-secondary btn-sm">Search</button>
                                                        @if(request()->has('search') || request()->has('filter'))
                                                            <a href="{{ route('branch.remittance.index') }}" class="btn btn-outline-warning btn-sm ml-2">Clear</a>
                                                        @endif
                                                    </form>
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-striped table-bordered preview-table">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 90px;">Status</th>
                                                            <th>EmpId</th>
                                                            <th>Name</th>
                                                            <th class="text-right">Loans</th>
                                                            <th class="text-right">Savings</th>
                                                            <th>Message</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($preview as $row)
                                                            <tr>
                                                                <td>
                                                                    @if ($row->status === 'success')
                                                                        <span class="badge badge-success">
                                                                            <i class="fa fa-check"></i> Matched
                                                                        </span>
                                                                    @else
                                                                        <span class="badge badge-danger">
                                                                            <i class="fa fa-times"></i> Unmatched
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                                <td>{{ $row->emp_id }}</td>
                                                                <td>{{ $row->name }}</td>
                                                                <td class="text-right">
                                                                    ₱{{ number_format($row->loans ?? 0, 2) }}
                                                                </td>
                                                                <td class="text-right">
                                                                    @php
                                                                        $savingsTotal = 0;
                                                                        $savingsDistribution = [];
                                                                        if (is_array($row->savings) && isset($row->savings['total'])) {
                                                                            $savingsTotal = $row->savings['total'];
                                                                            $savingsDistribution = $row->savings['distribution'] ?? [];
                                                                        } elseif (is_array($row->savings)) {
                                                                            $savingsTotal = collect($row->savings)->sum();
                                                                            foreach ($row->savings as $productName => $amount) {
                                                                                if ($amount > 0) {
                                                                                    $savingsDistribution[] = [
                                                                                        'product' => $productName,
                                                                                        'amount' => $amount
                                                                                    ];
                                                                                }
                                                                            }
                                                                        }
                                                                    @endphp
                                                                    ₱{{ number_format($savingsTotal, 2) }}
                                                                    @if(count($savingsDistribution) > 0)
                                                                        <br><small class="text-muted">
                                                                            @foreach($savingsDistribution as $dist)
                                                                                {{ $dist['product'] }}: ₱{{ number_format($dist['amount'], 2) }}
                                                                                @if(!$loop->last), @endif
                                                                            @endforeach
                                                                        </small>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if ($row->status !== 'success')
                                                                        <i class="fa fa-exclamation-circle text-danger"></i>
                                                                    @endif
                                                                    {{ $row->message }}
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="6" class="text-center">
                                                                    <div class="py-4">
                                                                        <i class="fa fa-info-circle fa-2x text-muted mb-2"></i>
                                                                        <p class="text-muted">No records found.</p>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-5">
                                                <i class="fa fa-file-excel fa-4x text-muted mb-3"></i>
                                                <h4 class="text-muted">No remittance data available for your branch</h4>
                                                <p class="text-muted">Remittance data is uploaded by admin and will appear here when available for your branch members.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @if($preview && $preview->count() > 0)
                                    <div class="d-flex justify-content-center mt-4 text-center">
                                        {{ $preview->appends(request()->query())->links() }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
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
                <p>Copyright © Designed &amp; Developed by <a href="https://mass-specc.coop/"
                        target="_blank">MASS-SPECC COOPERATIVE</a>2025</p>
            </div>
        </div>
    </div>

    @include('layouts.partials.footer')

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Custom file input
            $('.custom-file-input').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });

        function showExportLoading(type) {
            Swal.fire({
                title: 'Generating Export...',
                html: `Please wait while we generate the ${type} collection file for your branch. This may take a few moments.`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
    </script>
</body>

</html>
