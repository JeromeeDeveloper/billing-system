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

        .btn.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }

        .btn.disabled:hover {
            opacity: 0.6;
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
                                                @php
                                                    $loansSavingsEnabled = $exportStatuses->get('loans_savings') ? $exportStatuses->get('loans_savings')->is_enabled : true;
                                                    $loansSavingsWithProductEnabled = $exportStatuses->get('loans_savings_with_product') ? $exportStatuses->get('loans_savings_with_product')->is_enabled : true;
                                                @endphp
                                                <p class="text-muted small">Generate collection file for loans and savings remittance data.</p>
                                                <a href="{{ $loansSavingsEnabled ? route('branch.remittance.generateExport', ['type' => 'loans_savings']) : 'javascript:void(0)' }}" class="btn btn-primary btn-block {{ !$loansSavingsEnabled ? 'disabled' : '' }}">
                                                    <i class="fa fa-download"></i> Collection File for Loans & Savings
                                                    @if(!$loansSavingsEnabled)
                                                        <br><small class="text-muted">(Disabled - Upload new remittance to enable)</small>
                                                    @endif
                                                </a>
                                                <a href="{{ $loansSavingsWithProductEnabled ? route('branch.remittance.generateExport', ['type' => 'loans_savings_with_product']) : 'javascript:void(0)' }}" class="btn btn-outline-primary btn-block mt-2 {{ !$loansSavingsWithProductEnabled ? 'disabled' : '' }}">
                                                    <i class="fa fa-download"></i> Collection File for Loans & Savings (with Product Name)
                                                    @if(!$loansSavingsWithProductEnabled)
                                                        <br><small class="text-muted">(Disabled - Upload new remittance to enable)</small>
                                                    @endif
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
                                                @php
                                                    $sharesEnabled = $exportStatuses->get('shares') ? $exportStatuses->get('shares')->is_enabled : true;
                                                    $sharesWithProductEnabled = $exportStatuses->get('shares_with_product') ? $exportStatuses->get('shares_with_product')->is_enabled : true;
                                                @endphp
                                                <p class="text-muted small">Generate collection file for shares remittance data.</p>
                                                <a href="{{ $sharesEnabled ? route('branch.remittance.generateExport', ['type' => 'shares']) : 'javascript:void(0)' }}" class="btn btn-success btn-block text-white {{ !$sharesEnabled ? 'disabled' : '' }}">
                                                    <i class="fa fa-download"></i> Collection File for Shares
                                                    @if(!$sharesEnabled)
                                                        <br><small class="text-muted">(Disabled - Upload new shares to enable)</small>
                                                    @endif
                                                </a>
                                                <a href="{{ $sharesWithProductEnabled ? route('branch.remittance.generateExport', ['type' => 'shares_with_product']) : 'javascript:void(0)' }}" class="btn btn-outline-success btn-block mt-2 {{ !$sharesWithProductEnabled ? 'disabled' : '' }}">
                                                    <i class="fa fa-download"></i> Collection File for Shares (with Product Name)
                                                    @if(!$sharesWithProductEnabled)
                                                        <br><small class="text-muted">(Disabled - Upload new shares to enable)</small>
                                                    @endif
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Consolidated Remittance Table -->
                                        <div class="mb-3 text-right">
                                                            <a href="{{ route('branchRemittance.exportRegularSpecial') }}" class="btn btn-success">
                    <i class="fa fa-file-excel-o"></i> Export Regular & Special Billing Remittance
                </a>

                                        </div>

                                        @include('components.branch.remittance.consolidated_remittance_table_branch')
                                        {{-- If variables are missing, add a comment for the developer --}}
                                        @if (!isset($loansSavingsPreviewPaginated) || !isset($sharesPreviewPaginated) || !isset($regularRemittances) || !isset($specialRemittances) || !isset($regularBilled) || !isset($specialBilled))
                                            <div class="alert alert-warning mt-4">
                                                <strong>Note:</strong> Please ensure the controller passes <code>$loansSavingsPreviewPaginated</code>, <code>$sharesPreviewPaginated</code>, <code>$regularRemittances</code>, <code>$specialRemittances</code>, <code>$regularBilled</code>, and <code>$specialBilled</code> to this view, as in the admin remittance controller.
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

            // Show floating toast on page load for consolidated table
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'success',
                title: 'Consolidated Remittance View',
                text: 'All remittance data is now consolidated in one table with filters.',
                showConfirmButton: false,
                timer: 8000,
                timerProgressBar: true
            });
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
