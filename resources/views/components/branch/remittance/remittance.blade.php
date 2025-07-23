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
                                                <h6 class="mb-0 text-white"><i class="fa fa-file-excel"></i> Loans & Savings Collection</h6>
                                            </div>
                                            <div class="card-body">
                                                <p class="text-muted small">Generate collection file for loans and savings remittance data.</p>
                                                <a href="{{ route('branch.remittance.generateExport', ['type' => 'loans_savings']) }}" class="btn btn-primary btn-block">
                                                    <i class="fa fa-download"></i> Collection File for Loans & Savings
                                                </a>
                                                <a href="{{ route('branch.remittance.generateExport', ['type' => 'loans_savings_with_product']) }}" class="btn btn-outline-primary btn-block mt-2">
                                                    <i class="fa fa-download"></i> Collection File for Loans & Savings (with Product Name)
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-success">
                                            <div class="card-header bg-success text-white">
                                                <h6 class="mb-0 text-white"><i class="fa fa-file-excel"></i> Shares Collection</h6>
                                            </div>
                                            <div class="card-body">
                                                <p class="text-muted small">Generate collection file for shares remittance data.</p>
                                                <a href="{{ route('branch.remittance.generateExport', ['type' => 'shares']) }}" class="btn btn-success btn-block  text-white">
                                                    <i class="fa fa-download"></i> Collection File for Shares
                                                </a>
                                                <a href="{{ route('branch.remittance.generateExport', ['type' => 'shares_with_product']) }}" class="btn btn-outline-success btn-block mt-2">
                                                    <i class="fa fa-download"></i> Collection File for Shares (with Product Name)
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Preview Section -->
                                <div class="row">
                                    <div class="col-12">
                                        {{-- Loans & Savings Remittance Preview --}}
                                        <div class="card mb-4">
                                            <div class="card-body">
                                                <h5 class="text-center">Loans & Savings Remittance Preview</h5>
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
                                                            @forelse($loansSavingsPreviewPaginated ?? [] as $row)
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
                                                        {{ $loansSavingsPreviewPaginated->appends(request()->except('loans_page'))->links() ?? '' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Shares Remittance Preview --}}
                                        <div class="card mb-4">
                                            <div class="card-body">
                                                <h5 class="text-center">Shares Remittance Preview</h5>
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
                                                            @forelse($sharesPreviewPaginated ?? [] as $row)
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
                                                        {{ $sharesPreviewPaginated->appends(request()->except('shares_page'))->links() ?? '' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Billed vs Remitted Comparison Report --}}
                                        @if (isset($comparisonReportPaginated) && $comparisonReportPaginated->count() > 0)
                                            <div class="card mb-4">
                                                <div class="card-header">
                                                    <h5 class="mb-0">Billed vs Remitted Comparison Report</h5>
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
                                                                    <th>Remaining Amort Due</th>
                                                                    <th>Remitted Savings</th>
                                                                    <th>Remitted Shares</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @forelse ($comparisonReportPaginated as $row)
                                                                    <tr>
                                                                        <td>{{ $row['cid'] }}</td>
                                                                        <td>{{ $row['member_name'] }}</td>
                                                                        <td>₱{{ number_format($row['amortization'], 2) }}</td>
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
                                        @endif
                                        {{-- If variables are missing, add a comment for the developer --}}
                                        @if (!isset($loansSavingsPreviewPaginated) || !isset($sharesPreviewPaginated) || !isset($comparisonReportPaginated))
                                            <div class="alert alert-warning mt-4">
                                                <strong>Note:</strong> Please ensure the controller passes <code>$loansSavingsPreviewPaginated</code>, <code>$sharesPreviewPaginated</code>, and <code>$comparisonReportPaginated</code> to this view, as in the admin remittance controller.
                                            </div>
                                        @endif
                                    </div>
                                </div>
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
            }, 25000);

            // Show floating toast on page load ONLY if both preview tables have no records
            var hasLoansSavingsRecords = false;
            var hasSharesRecords = false;
            $(".card-body").each(function() {
                var header = $(this).find('h5.text-center').text().trim();
                if (header === 'Loans & Savings Remittance Preview') {
                    var rows = $(this).find('table tbody tr');
                    hasLoansSavingsRecords = rows.filter(function() {
                        return !$(this).text().includes('No records found.');
                    }).length > 0;
                }
                if (header === 'Shares Remittance Preview') {
                    var rows = $(this).find('table tbody tr');
                    hasSharesRecords = rows.filter(function() {
                        return !$(this).text().includes('No records found.');
                    }).length > 0;
                }
            });
            if (!hasLoansSavingsRecords && !hasSharesRecords) {
                Swal.fire({
                    toast: true,
                    position: 'bottom-end',
                    icon: 'info',
                    title: 'No records yet for collection.',
                    text: 'Please wait for the admin to upload remittance files.',
                    showConfirmButton: false,
                    timer: 8000,
                    timerProgressBar: true
                });
            } else {
                Swal.fire({
                    toast: true,
                    position: 'bottom-end',
                    icon: 'success',
                    title: "It's good to generate collection files now!",
                    text: 'You may proceed to export your branch\'s collection files.',
                    showConfirmButton: false,
                    timer: 8000,
                    timerProgressBar: true
                });
            }
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
