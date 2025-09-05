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

        /* Monitoring Dashboard Styles */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .border-left-success {
            border-left-color: #28a745 !important;
        }

        .border-left-warning {
            border-left-color: #ffc107 !important;
        }

        .stat-item {
            padding: 10px;
            border-radius: 8px;
            background: rgba(0,0,0,0.02);
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            background: rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }

        .card.shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }

        .badge-pill {
            padding: 0.5em 1em;
            font-size: 0.75em;
        }

        .card-header.bg-gradient-primary {
            border-bottom: none;
        }

        .alert-info {
            background-color: rgba(23, 162, 184, 0.1);
            border-color: rgba(23, 162, 184, 0.2);
        }

        /* Detailed Monitoring Styles */
        .detail-item {
            margin-bottom: 8px;
        }

        .detail-item small {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .detail-item .font-weight-bold {
            color: #495057;
        }

        .card-header.bg-light {
            border-bottom: 1px solid #dee2e6;
        }

        .border-left-success {
            border-left-color: #28a745 !important;
        }

        .border-left-warning {
            border-left-color: #ffc107 !important;
        }

        .stat-item {
            padding: 12px;
            border-radius: 8px;
            background: rgba(0,0,0,0.02);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .stat-item:hover {
            background: rgba(0,0,0,0.05);
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card.shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }

        .badge-pill {
            padding: 0.5em 1em;
            font-size: 0.75em;
        }

        .card-header.bg-gradient-primary {
            border-bottom: none;
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

                                                                <!-- Collection Monitoring Dashboard -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-gradient-primary text-white">
                                                <h5 class="mb-0">
                                                    <i class="fa fa-tachometer-alt"></i> Collection Monitoring Dashboard
                                                </h5>
                                                <small class="text-white-50">Comprehensive overview of collection generation status and data availability</small>
                                            </div>
                                            <div class="card-body">
                                                <!-- Upload Count Monitoring Cards (Same as Admin) -->
                                                <div class="row mb-4">
                                                    <div class="col-md-4">
                                                        <div class="card stats-card bg-light border-primary">
                                                            <div class="card-body text-center">
                                                                <h5 class="card-title text-primary mb-2">
                                                                    <i class="fa fa-upload"></i> Remittance (Regular)
                                                                </h5>
                                                                <h2 class="mb-0">{{ $remittanceImportRegularCount }}</h2>
                                                                @php
                                                                    $regularCount = $remittanceImportRegularCount;
                                                                    $ordinal = '';
                                                                    if ($regularCount == 1) {
                                                                        $ordinal = '1st';
                                                                    } elseif ($regularCount == 2) {
                                                                        $ordinal = '2nd';
                                                                    } elseif ($regularCount == 3) {
                                                                        $ordinal = '3rd';
                                                                    } else {
                                                                        $ordinal = $regularCount . 'th';
                                                                    }
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
                                                                <h2 class="mb-0">{{ $remittanceImportSpecialCount }}</h2>
                                                                @php
                                                                    $specialCount = $remittanceImportSpecialCount;
                                                                    $ordinal = '';
                                                                    if ($specialCount == 1) {
                                                                        $ordinal = '1st';
                                                                    } elseif ($specialCount == 2) {
                                                                        $ordinal = '2nd';
                                                                    } elseif ($specialCount == 3) {
                                                                        $ordinal = '3rd';
                                                                    } else {
                                                                        $ordinal = $specialCount . 'th';
                                                                    }
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
                                                                <h2 class="mb-0">{{ $sharesRemittanceImportCount }}</h2>
                                                                @php
                                                                    $sharesCount = $sharesRemittanceImportCount;
                                                                    $ordinal = '';
                                                                    if ($sharesCount == 1) {
                                                                        $ordinal = '1st';
                                                                    } elseif ($sharesCount == 2) {
                                                                        $ordinal = '2nd';
                                                                    } elseif ($sharesCount == 3) {
                                                                        $ordinal = '3rd';
                                                                    } else {
                                                                        $ordinal = $sharesCount . 'th';
                                                                    }
                                                                @endphp
                                                                <small class="text-muted">{{ $ordinal }} remittance uploaded this period</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Detailed Monitoring Cards -->
                                                <div class="row">
                                                    <!-- Loans & Savings Detailed Monitoring -->
                                                    <div class="col-md-6 mb-4">
                                                        <div class="card h-100 border-left" style="border-left-width: 4px;">
                                                            <div class="card-header bg-light">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <h6 class="mb-0 text-primary">
                                                                        <i class="fa fa-money-bill-wave"></i> Loans & Savings Collection
                                                                    </h6>
                                                                </div>
                                                            </div>
                                                            <div class="card-body">
                                                                <!-- Statistics Grid -->
                                                                <div class="row text-center mb-3">
                                                                    <div class="col-4">
                                                                        <div class="stat-item">
                                                                            <h4 class="text-success mb-0">{{ $monitoringData['loans_savings']['total_records'] }}</h4>
                                                                            <small class="text-muted">Total Records</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-4">
                                                                        <div class="stat-item">
                                                                            <h4 class="text-info mb-0">{{ $monitoringData['loans_savings']['matched_records'] }}</h4>
                                                                            <small class="text-muted">Matched Records</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-4">
                                                                        <div class="stat-item">
                                                                            <h4 class="text-warning mb-0">{{ $collectionStatus['loans_savings']['match_rate'] }}%</h4>
                                                                            <small class="text-muted">Match Rate</small>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Detailed Information -->
                                                                @if($monitoringData['loans_savings']['latest_batch'])
                                                                    <div class="mt-3">
                                                                        <h6 class="text-muted mb-2"><i class="fa fa-info-circle"></i> Latest Import Details</h6>
                                                                        <div class="row">
                                                                            <div class="col-6">
                                                                                <div class="detail-item">
                                                                                    <small class="text-muted">Import Date:</small>
                                                                                    <div class="font-weight-bold">
                                                                                        {{ \Carbon\Carbon::parse($monitoringData['loans_savings']['latest_batch']->imported_at)->format('M d, Y') }}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <div class="detail-item">
                                                                                    <small class="text-muted">Import Time:</small>
                                                                                    <div class="font-weight-bold">
                                                                                        {{ \Carbon\Carbon::parse($monitoringData['loans_savings']['latest_batch']->imported_at)->format('g:i A') }}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mt-2">
                                                                            <div class="col-6">
                                                                                <div class="detail-item">
                                                                                    <small class="text-muted">Billing Type:</small>
                                                                                    <div>
                                                                                        <span class="badge badge-{{ $monitoringData['loans_savings']['latest_batch']->billing_type === 'regular' ? 'primary' : 'success' }}">
                                                                                            {{ ucfirst($monitoringData['loans_savings']['latest_batch']->billing_type) }}
                                                                                        </span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                                                                        <div class="col-6">
                                                <div class="detail-item">
                                                    <small class="text-muted">Latest Upload:</small>
                                                    <div class="font-weight-bold">
                                                        @if($remittanceImportRegularCount > 0)
                                                            {{ $remittanceImportRegularCount }}{{ $remittanceImportRegularCount == 1 ? 'st' : ($remittanceImportRegularCount == 2 ? 'nd' : ($remittanceImportRegularCount == 3 ? 'rd' : 'th')) }} Upload
                                                        @else
                                                            No Uploads
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                                                        </div>
                                                                        <div class="row mt-2">
                                                                            <div class="col-12">
                                                                                <div class="detail-item">
                                                                                    <small class="text-muted">Time Since Import:</small>
                                                                                    <div class="font-weight-bold text-info">
                                                                                        {{ \Carbon\Carbon::parse($monitoringData['loans_savings']['latest_batch']->imported_at)->diffForHumans() }}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <div class="mt-3 text-center">
                                                                        <div class="alert alert-warning mb-0">
                                                                            <i class="fa fa-exclamation-triangle"></i>
                                                                            <strong>No Import Data Available</strong><br>
                                                                            <small>No remittance batches found for loans & savings in this billing period.</small>
                                                                        </div>
                                                                    </div>
                                                                @endif

                                                                                                                                 <!-- Available Types -->
                                                                 @if($monitoringData['loans_savings']['available_types']->count() > 0)
                                                                     <div class="mt-3">
                                                                         <h6 class="text-muted mb-2"><i class="fa fa-tags"></i> Available Billing Types</h6>
                                                                         <div class="d-flex flex-wrap">
                                                                             @foreach($monitoringData['loans_savings']['available_types'] as $type)
                                                                                 <span class="badge badge-{{ $type === 'regular' ? 'primary' : 'success' }} mr-2 mb-1">
                                                                                     {{ ucfirst($type) }}
                                                                                 </span>
                                                                             @endforeach
                                                                         </div>
                                                                     </div>
                                                                 @endif

                                                                 <!-- Generation Status -->
                                                                 {{-- <div class="mt-3">
                                                                     <h6 class="text-muted mb-2"><i class="fa fa-download"></i> Generation Status</h6>
                                                                     <div class="row">
                                                                         <div class="col-6">
                                                                             <div class="detail-item">
                                                                                 <small class="text-muted">Basic Collection:</small>
                                                                                 <div>
                                                                                     @if($collectionStatus['loans_savings']['last_generated'])
                                                                                         <span class="badge badge-success badge-pill">
                                                                                             <i class="fa fa-check"></i> Generated
                                                                                         </span>
                                                                                         <br><small class="text-muted">{{ \Carbon\Carbon::parse($collectionStatus['loans_savings']['last_generated'])->format('M d, g:i A') }}</small>
                                                                                     @else
                                                                                         <span class="badge badge-secondary badge-pill">
                                                                                             <i class="fa fa-clock"></i> Not Generated
                                                                                         </span>
                                                                                     @endif
                                                                                 </div>
                                                                             </div>
                                                                         </div>
                                                                         <div class="col-6">
                                                                             <div class="detail-item">
                                                                                 <small class="text-muted">With Product Names:</small>
                                                                                 <div>
                                                                                     @if($collectionStatus['loans_savings_with_product']['last_generated'])
                                                                                         <span class="badge badge-success badge-pill">
                                                                                             <i class="fa fa-check"></i> Generated
                                                                                         </span>
                                                                                         <br><small class="text-muted">{{ \Carbon\Carbon::parse($collectionStatus['loans_savings_with_product']['last_generated'])->format('M d, g:i A') }}</small>
                                                                                     @else
                                                                                         <span class="badge badge-secondary badge-pill">
                                                                                             <i class="fa fa-clock"></i> Not Generated
                                                                                         </span>
                                                                                     @endif
                                                                                 </div>
                                                                             </div>
                                                                         </div>
                                                                     </div>
                                                                 </div> --}}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Shares Detailed Monitoring -->
                                                    <div class="col-md-6 mb-4">
                                                        <div class="card h-100 border-left" style="border-left-width: 4px;">
                                                            <div class="card-header bg-light">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <h6 class="mb-0 text-success">
                                                                        <i class="fa fa-chart-pie"></i> Shares Collection
                                                                    </h6>
                                                                </div>
                                                            </div>
                                                            <div class="card-body">
                                                                <!-- Statistics Grid -->
                                                                <div class="row text-center mb-3">
                                                                    <div class="col-4">
                                                                        <div class="stat-item">
                                                                            <h4 class="text-success mb-0">{{ $monitoringData['shares']['total_records'] }}</h4>
                                                                            <small class="text-muted">Total Records</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-4">
                                                                        <div class="stat-item">
                                                                            <h4 class="text-info mb-0">{{ $monitoringData['shares']['matched_records'] }}</h4>
                                                                            <small class="text-muted">Matched Records</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-4">
                                                                        <div class="stat-item">
                                                                            <h4 class="text-warning mb-0">{{ $collectionStatus['shares']['match_rate'] }}%</h4>
                                                                            <small class="text-muted">Match Rate</small>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Detailed Information -->
                                                                @if($monitoringData['shares']['latest_batch'])
                                                                    <div class="mt-3">
                                                                        <h6 class="text-muted mb-2"><i class="fa fa-info-circle"></i> Latest Import Details</h6>
                                                                        <div class="row">
                                                                            <div class="col-6">
                                                                                <div class="detail-item">
                                                                                    <small class="text-muted">Import Date:</small>
                                                                                    <div class="font-weight-bold">
                                                                                        {{ \Carbon\Carbon::parse($monitoringData['shares']['latest_batch']->imported_at)->format('M d, Y') }}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <div class="detail-item">
                                                                                    <small class="text-muted">Import Time:</small>
                                                                                    <div class="font-weight-bold">
                                                                                        {{ \Carbon\Carbon::parse($monitoringData['shares']['latest_batch']->imported_at)->format('g:i A') }}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mt-2">
                                                                            <div class="col-6">
                                                                                <div class="detail-item">
                                                                                    <small class="text-muted">Billing Type:</small>
                                                                                    <div>
                                                                                        <span class="badge badge-info">
                                                                                            Shares
                                                                                        </span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                                                                        <div class="col-6">
                                                <div class="detail-item">
                                                    <small class="text-muted">Latest Upload:</small>
                                                    <div class="font-weight-bold">
                                                        @if($sharesRemittanceImportCount > 0)
                                                            {{ $sharesRemittanceImportCount }}{{ $sharesRemittanceImportCount == 1 ? 'st' : ($sharesRemittanceImportCount == 2 ? 'nd' : ($sharesRemittanceImportCount == 3 ? 'rd' : 'th')) }} Upload
                                                        @else
                                                            No Uploads
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                                                        </div>
                                                                        <div class="row mt-2">
                                                                            <div class="col-12">
                                                                                <div class="detail-item">
                                                                                    <small class="text-muted">Time Since Import:</small>
                                                                                    <div class="font-weight-bold text-info">
                                                                                        {{ \Carbon\Carbon::parse($monitoringData['shares']['latest_batch']->imported_at)->diffForHumans() }}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <div class="mt-3 text-center">
                                                                        <div class="alert alert-warning mb-0">
                                                                            <i class="fa fa-exclamation-triangle"></i>
                                                                            <strong>No Import Data Available</strong><br>
                                                                            <small>No remittance batches found for shares in this billing period.</small>
                                                                        </div>
                                                                    </div>
                                                                @endif

                                                                                                                                 <!-- Available Types -->
                                                                 @if($monitoringData['shares']['available_types']->count() > 0)
                                                                     <div class="mt-3">
                                                                         <h6 class="text-muted mb-2"><i class="fa fa-tags"></i> Available Billing Types</h6>
                                                                         <div class="d-flex flex-wrap">
                                                                             @foreach($monitoringData['shares']['available_types'] as $type)
                                                                                 <span class="badge badge-info mr-2 mb-1">
                                                                                     {{ ucfirst($type) }}
                                                                                 </span>
                                                                             @endforeach
                                                                         </div>
                                                                     </div>
                                                                 @endif

                                                                 <!-- Generation Status -->


                                                                         {{-- <div class="col-6">
                                                                             <div class="detail-item">
                                                                                 <small class="text-muted">With Product Names:</small>
                                                                                 <div>
                                                                                     @if($collectionStatus['shares_with_product']['last_generated'])
                                                                                         <span class="badge badge-success badge-pill">
                                                                                             <i class="fa fa-check"></i> Generated
                                                                                         </span>
                                                                                         <br><small class="text-muted">{{ \Carbon\Carbon::parse($collectionStatus['shares_with_product']['last_generated'])->format('M d, g:i A') }}</small>
                                                                                     @else
                                                                                         <span class="badge badge-secondary badge-pill">
                                                                                             <i class="fa fa-clock"></i> Not Generated
                                                                                         </span>
                                                                                     @endif
                                                                                 </div>
                                                                             </div>
                                                                         </div> --}}
                                                                     </div>
                                                                 </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Detailed Information Panel -->

                                            </div>
                                        </div>
                                    </div>
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

                                <!-- Reports Section -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-gradient-info text-white">
                                                <h5 class="mb-0">
                                                    <i class="fa fa-file-alt"></i> Remittance Reports
                                                </h5>
                                                <small class="text-white-50">Generate comprehensive remittance reports for your branch</small>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">

                                                    <div class="col-md-4 mb-3">
                                                        <div class="card border-success h-100">
                                                            <div class="card-header bg-success text-white">
                                                                <h6 class="mb-0">
                                                                    <i class="fa fa-file-excel"></i> Members not processed
                                                                </h6>
                                                            </div>
                                                            <div class="card-body text-center">
                                                                <p class="text-muted mb-3">
                                                                    Generate a report showing unmatched remittance records.
                                                                </p>
                                                                <a href="{{ route('branchRemittance.exportConsolidated') }}" class="btn btn-success btn-lg">
                                                                    <i class="fa fa-download"></i> Export Unmatched
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <div class="card border-info h-100">
                                                            <div class="card-header bg-info text-white">
                                                                <h6 class="mb-0">
                                                                    <i class="fa fa-file-excel"></i> Summary (Regular)
                                                                </h6>
                                                            </div>
                                                            <div class="card-body text-center">
                                                                <p class="text-muted mb-3">
                                                                    Export summary report for regular billing type members only.
                                                                </p>
                                                                <a href="{{ route('branch.remittance.exportPerRemittanceSummaryRegular') }}" class="btn btn-info btn-lg">
                                                                    <i class="fa fa-download"></i> Export Summary (Regular)
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <div class="card border-warning h-100">
                                                            <div class="card-header bg-warning text-white">
                                                                <h6 class="mb-0">
                                                                    <i class="fa fa-file-excel"></i> Summary (Special)
                                                                </h6>
                                                            </div>
                                                            <div class="card-body text-center">
                                                                <p class="text-muted mb-3">
                                                                    Export summary report for special billing type members only.
                                                                </p>
                                                                <a href="{{ route('branch.remittance.exportPerRemittanceSummarySpecial') }}" class="btn btn-warning btn-lg">
                                                                    <i class="fa fa-download"></i> Export Summary (Special)
                                                                </a>
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

        // Monitoring Dashboard Enhancements
        $(document).ready(function() {
            // Add hover effects to monitoring cards
            $('.card.border-left-success, .card.border-left-warning').hover(
                function() {
                    $(this).addClass('shadow');
                },
                function() {
                    $(this).removeClass('shadow');
                }
            );

            // Removed click to refresh functionality to prevent accidental page reloads

            // Show tooltips for better UX
            $('[data-toggle="tooltip"]').tooltip();

            // Auto-refresh monitoring data every 30 seconds
            setInterval(function() {
                // You can add AJAX call here to refresh monitoring data without full page reload
                console.log('Monitoring data can be refreshed via AJAX');
            }, 30000);
        });
    </script>
</body>

</html>
