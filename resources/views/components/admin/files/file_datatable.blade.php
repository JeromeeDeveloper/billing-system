<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Billing and Collection</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">

    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css') }}" rel="stylesheet">

    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

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
                            <h4>File Uploads</h4>
                            <span class="ml-1">Datatable</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active"><a href="{{ route('billing') }}">Billing</a></li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="card-title mb-0">File Datatable</h4>
                                    <small class="text-muted">Showing latest 5 files total for current billing
                                        period</small>
                                </div>
                                <div class="d-flex align-items-center" style="gap: 10px;">
                                    <a href="{{ route('file.retention.dashboard') }}" class="btn btn-rounded btn-info">
                                        <span class="btn-icon-left text-info">
                                            <i class="fa fa-cogs"></i>
                                        </span>
                                        File Retention
                                    </a>
                                    @php
                                        // Determine if any admin or branch users are already approved
                                        $hasApprovedBranches = isset($hasApprovedBranches)
                                            ? $hasApprovedBranches
                                            : \App\Models\User::whereIn('role', ['admin', 'branch'])
                                                ->where('billing_approval_status', 'approved')
                                                ->exists();
                                    @endphp
                                    <button type="button" class="btn btn-rounded btn-primary" data-toggle="modal"
                                        data-target="#exampleModalpopover">
                                        <span class="btn-icon-left text-primary">
                                            <i class="fa fa-upload"></i>
                                        </span>
                                        Upload
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="exampleModalpopover" tabindex="-1" role="dialog"
                                aria-labelledby="exampleModalpopoverLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <form id="uploadForm" action="{{ route('document.upload') }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf

                                            <div class="modal-body">

                                                @php
                                                    $billingPeriod = auth()->user()->billing_period
                                                        ? \Carbon\Carbon::parse(auth()->user()->billing_period)->format(
                                                            'F Y',
                                                        )
                                                        : 'N/A';
                                                    // Ensure hasApprovedBranches is defined
                                                    $hasApprovedBranches = isset($hasApprovedBranches)
                                                        ? $hasApprovedBranches
                                                        : \App\Models\User::whereIn('role', ['admin', 'branch'])
                                                            ->where('billing_approval_status', 'approved')
                                                            ->exists();
                                                @endphp

                                                <div class="form-group">
                                                    <label class="font-weight-bold text-dark mb-2">Billing
                                                        Period</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $billingPeriod }}" readonly>
                                                </div>


                                                <div class="form-group">
                                                    <label for="file" class="font-weight-bold text-dark mb-2">📁
                                                        Installment
                                                        Forecast
                                                        File
                                                    </label>
                                                    <div class="form-check mb-2">
                                                        @php
                                                            $consolidatedDisabled = $hasApprovedBranches;
                                                        @endphp
                                                        <input class="form-check-input" type="radio"
                                                            name="forecast_type" id="forecast_consolidated"
                                                            value="consolidated"
                                                            {{ $consolidatedDisabled ? 'disabled checked' : 'checked' }}>
                                                        <label class="form-check-label text-dark"
                                                            for="forecast_consolidated">
                                                            <strong class="text-dark">Consolidated</strong> - Upload for
                                                            all branches
                                                            @if ($consolidatedDisabled)
                                                                <span class="badge badge-warning ml-2">Disabled: at
                                                                    least one admin or branch user is approved</span>
                                                            @endif
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="radio"
                                                            name="forecast_type" id="forecast_branch" value="branch">
                                                        <label class="form-check-label text-dark" for="forecast_branch">
                                                            <strong class="text-dark">Per Branch</strong> - Upload for
                                                            specific branch only (shows as "Branch Forecast - [Branch
                                                            Name]")
                                                        </label>
                                                    </div>
                                                    <div class="form-group" id="branch_selection_group"
                                                        style="display: none;">
                                                        <label for="branch_id"
                                                            class="font-weight-bold text-dark mb-2">Select
                                                            Branch</label>
                                                        <select class="form-control" id="branch_id" name="branch_id">
                                                            <option value="">Select a branch...</option>
                                                            @php $branches = \App\Models\Branch::orderBy('name')->get(); @endphp
                                                            @foreach ($branches as $branch)
                                                                @php
                                                                    $branchUser = \App\Models\User::where(
                                                                        'role',
                                                                        'branch',
                                                                    )
                                                                        ->where('branch_id', $branch->id)
                                                                        ->first();
                                                                    $branchApproved =
                                                                        $branchUser &&
                                                                        $branchUser->billing_approval_status ===
                                                                            'approved';
                                                                    $disabled = $branchApproved;
                                                                @endphp
                                                                <option value="{{ $branch->id }}"
                                                                    {{ $disabled ? 'disabled' : '' }}>
                                                                    {{ $branch->name }}
                                                                    {{ $branchApproved ? '(Approved - disabled)' : '' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Branches marked as Approved are
                                                            disabled and cannot be selected.</small>
                                                    </div>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input"
                                                            id="file" name="file" accept=".csv" required>
                                                        <label class="custom-file-label" for="file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="savings_file"
                                                        class="font-weight-bold text-dark mb-2">💰 Savings
                                                        File</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input"
                                                            id="savings_file" name="savings_file" accept=".csv"
                                                            required>
                                                        <label class="custom-file-label" for="savings_file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="shares_file"
                                                        class="font-weight-bold text-dark mb-2">📊 Shares
                                                        File</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input"
                                                            id="shares_file" name="shares_file" accept=".csv"
                                                            required>
                                                        <label class="custom-file-label" for="shares_file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="cif_file" class="font-weight-bold text-dark mb-2">👤
                                                        CIF File
                                                    </label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input"
                                                            id="cif_file" name="cif_file" accept=".csv" required>
                                                        <label class="custom-file-label" for="cif_file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="loan_file" class="font-weight-bold text-dark mb-2">🏛️
                                                        Loans
                                                        File
                                                    </label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input"
                                                            id="loan_file" name="loan_file" accept=".csv" required>
                                                        <label class="custom-file-label" for="loan_file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="alert alert mb-3 mx-3 text-danger">
                                                <i class="fa fa-exclamation-triangle"></i>
                                                <strong class="text-danger">Important:</strong> Please ensure all files
                                                are correct before uploading.
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Upload</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            @endif
                            <div class="card-body">

                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>Filename</th>
                                                <th>Document Type</th>
                                                <th>File Path</th>
                                                <th>Billing Period</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($documents as $document)
                                                <tr>
                                                    <td>{{ $document->filename }}</td>
                                                    <td>{{ $document->document_type }}</td>
                                                    <td>{{ $document->filepath }}</td>
                                                    @php
                                                        $billingPeriod = $document->billing_period
                                                            ? \Carbon\Carbon::parse($document->billing_period)
                                                            : null;
                                                    @endphp

                                                    <td>
                                                        {{ $billingPeriod ? $billingPeriod->format('Y-m') : 'N/A' }}
                                                    </td>

                                                    <td>
                                                        <a class="btn btn-rounded btn-primary"
                                                            href="{{ asset('storage/' . $document->filepath) }}"
                                                            target="_blank">
                                                            <i class="fas fa-download me-1"></i> Download
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>

                                        <tfoot>
                                            <tr>
                                                <th>Filename</th>
                                                <th>Document Type</th>
                                                <th>File Path</th>
                                                <th>Billing Period</th>
                                                <th>Actions</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Floating View Format Button -->
                            <button id="floatingFormatBtn" class="btn btn-success btn-lg shadow" data-toggle="modal"
                                data-target="#viewFormatModal" title="View File Format Guide">
                                <i class="fa fa-eye"></i>
                            </button>

                            <!-- View Format Modal -->
                            <div class="modal fade" id="viewFormatModal" tabindex="-1" role="dialog"
                                aria-labelledby="viewFormatModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewFormatModalLabel">File Upload Format Guide
                                            </h5>
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-3 text-dark">Below are the required formats for each file
                                                type. Please ensure your files match these formats before uploading.</p>
                                            <div class="accordion" id="formatAccordion">
                                                <div class="card">
                                                    <div class="card-header" id="headingForecast">
                                                        <h2 class="mb-0">
                                                            <button class="btn btn-link" type="button"
                                                                data-toggle="collapse" data-target="#collapseForecast"
                                                                aria-expanded="true" aria-controls="collapseForecast">
                                                                📁 Installment Forecast File Format
                                                            </button>
                                                        </h2>
                                                    </div>
                                                    <div id="collapseForecast" class="collapse show"
                                                        aria-labelledby="headingForecast"
                                                        data-parent="#formatAccordion">
                                                        <div class="card-body">
                                                            <strong class="text-dark">Required Columns (Row 5 as
                                                                header):</strong>
                                                            <ul class="text-dark">
                                                                <li>Branch Name</li>
                                                                <li>Branch Code</li>
                                                                <li>CID</li>
                                                                <li>Name (Lastname, Firstname)</li>
                                                                <li>Loan Account No.</li>
                                                                <li>Open Date</li>
                                                                <li>Maturity Date</li>
                                                                <li>Amortization Due Date</li>
                                                                <li>Principal</li>
                                                                <li>Interest</li>
                                                                <li>Total Amort</li>
                                                                <li>Total Due</li>
                                                                <li>Principal Due</li>
                                                                <li>Interest Due</li>
                                                                <li>Penalty Due</li>
                                                            </ul>
                                                            <small class="text-muted">File type: .csv</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card">
                                                    <div class="card-header" id="headingSavings">
                                                        <h2 class="mb-0">
                                                            <button class="btn btn-link collapsed" type="button"
                                                                data-toggle="collapse" data-target="#collapseSavings"
                                                                aria-expanded="false" aria-controls="collapseSavings">
                                                                💰 Savings File Format
                                                            </button>
                                                        </h2>
                                                    </div>
                                                    <div id="collapseSavings" class="collapse"
                                                        aria-labelledby="headingSavings"
                                                        data-parent="#formatAccordion">
                                                        <div class="card-body">
                                                            <strong class="text-dark">Required Columns (Row 6 as
                                                                header):</strong>
                                                            <ul class="text-dark">
                                                                <li>Customer No.</li>
                                                                <li>Account No.</li>
                                                                <li>Product Code</li>
                                                                <li>Open Date</li>
                                                                <li>Current Balance</li>
                                                                <li>Available Balance</li>
                                                                <li>Interest Due Amount</li>
                                                                <li>Status</li>
                                                                <li>Last Transaction Date</li>
                                                            </ul>
                                                            <small class="text-muted">File type: .csv</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card">
                                                    <div class="card-header" id="headingShares">
                                                        <h2 class="mb-0">
                                                            <button class="btn btn-link collapsed" type="button"
                                                                data-toggle="collapse" data-target="#collapseShares"
                                                                aria-expanded="false" aria-controls="collapseShares">
                                                                📊 Shares File Format
                                                            </button>
                                                        </h2>
                                                    </div>
                                                    <div id="collapseShares" class="collapse"
                                                        aria-labelledby="headingShares"
                                                        data-parent="#formatAccordion">
                                                        <div class="card-body">
                                                            <strong class="text-dark">Required Columns (Row 6 as
                                                                header):</strong>
                                                            <ul class="text-dark">
                                                                <li>Customer No.</li>
                                                                <li>Account No.</li>
                                                                <li>Product Code</li>
                                                                <li>Open Date</li>
                                                                <li>Current Balance</li>
                                                                <li>Available Balance</li>
                                                                <li>Interest Due Amount</li>
                                                                <li>Status</li>
                                                                <li>Last Transaction Date</li>
                                                            </ul>
                                                            <small class="text-muted">File type: .csv</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card">
                                                    <div class="card-header" id="headingCIF">
                                                        <h2 class="mb-0">
                                                            <button class="btn btn-link collapsed" type="button"
                                                                data-toggle="collapse" data-target="#collapseCIF"
                                                                aria-expanded="false" aria-controls="collapseCIF">
                                                                👤 CIF File Format
                                                            </button>
                                                        </h2>
                                                    </div>
                                                    <div id="collapseCIF" class="collapse"
                                                        aria-labelledby="headingCIF" data-parent="#formatAccordion">
                                                        <div class="card-body">
                                                            <strong class="text-dark">Required Columns (Row 4 as
                                                                header):</strong>
                                                            <ul class="text-dark">
                                                                <li>Customer No.</li>
                                                                <li>Customer Name (Lastname, Firstname)</li>
                                                                <li>Birth Date</li>
                                                                <li>Date Registered</li>
                                                                <li>Gender</li>
                                                                <li>Customer Type</li>
                                                                <li>Customer Classification</li>
                                                                <li>Industry</li>
                                                                <li>Area Officer</li>
                                                                <li>Area</li>
                                                                <li>Status</li>
                                                                <li>Address</li>
                                                            </ul>
                                                            <small class="text-muted">File type: .csv</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card">
                                                    <div class="card-header" id="headingLoans">
                                                        <h2 class="mb-0">
                                                            <button class="btn btn-link collapsed" type="button"
                                                                data-toggle="collapse" data-target="#collapseLoans"
                                                                aria-expanded="false" aria-controls="collapseLoans">
                                                                🏛️ Loans File Format
                                                            </button>
                                                        </h2>
                                                    </div>
                                                    <div id="collapseLoans" class="collapse"
                                                        aria-labelledby="headingLoans" data-parent="#formatAccordion">
                                                        <div class="card-body">
                                                            <strong class="text-dark">Required Columns (Row 1 as
                                                                header):</strong>
                                                            <ul class="text-dark">
                                                                <li>CID (Column A)</li>
                                                                <li>Account No. (Column B)</li>
                                                                <li>Start Date (Column H)</li>
                                                                <li>End Date (Column I)</li>
                                                                <li>Principal (Column L)</li>
                                                            </ul>
                                                            <small class="text-muted">File type: .csv</small>
                                                            <br><small class="text-muted">Note: Other columns may exist
                                                                but are not required for import.</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <p class="mb-0 text-dark"><strong class="text-dark">Note:</strong> For
                                                best results, always use the provided template or sample file if
                                                available. If you have questions, contact your system administrator.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="footer">
            <div class="copyright">
                <p>Copyright © Designed &amp; Developed by <a href="https://mass-specc.coop/"
                        target="_blank">MASS-SPECC
                        COOPERATIVE</a>2025</p>
            </div>
        </div>

    </div>

    <script src="./vendor/global/global.min.js"></script>
    <script src="./js/quixnav-init.js"></script>
    <script src="./js/custom.min.js"></script>
    <script src="./vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="./js/plugins-init/datatables.init.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadForm = document.getElementById('uploadForm');

            if (uploadForm) {
                uploadForm.addEventListener('submit', function() {
                    Swal.fire({
                        title: 'Uploading...',
                        html: 'Please wait while the file is being processed.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                });
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var fileInputs = document.querySelectorAll('.custom-file-input');
            fileInputs.forEach(function(input) {
                input.addEventListener('change', function(e) {
                    var fileName = e.target.files.length > 0 ? e.target.files[0].name :
                        'Choose file...';
                    var label = input.nextElementSibling;
                    if (label && label.classList.contains('custom-file-label')) {
                        label.textContent = fileName;
                    }
                });
            });

            // Handle forecast type radio button selection
            var forecastConsolidated = document.getElementById('forecast_consolidated');
            var forecastBranch = document.getElementById('forecast_branch');
            var branchSelectionGroup = document.getElementById('branch_selection_group');
            var branchSelect = document.getElementById('branch_id');

            function toggleBranchSelection() {
                if (forecastBranch.checked) {
                    branchSelectionGroup.style.display = 'block';
                    // Only require selection if dropdown is enabled
                    branchSelect.required = !branchSelect.disabled;
                } else {
                    branchSelectionGroup.style.display = 'none';
                    branchSelect.required = false;
                    branchSelect.value = '';
                }
            }

            // Check if both radio buttons are disabled
            var bothDisabled = forecastConsolidated.disabled && forecastBranch.disabled;

            if (bothDisabled) {
                // If both are disabled, show a message and hide branch selection
                branchSelectionGroup.style.display = 'none';
                branchSelect.required = false;
                branchSelect.value = '';

                // Add a disabled message if it doesn't already exist
                if (!document.querySelector('.upload-disabled-message')) {
                    var disabledMessage = document.createElement('div');
                    disabledMessage.className =
                    'alert alert-warning text-center small mb-2 upload-disabled-message';
                    disabledMessage.innerHTML =
                        '<i class="fa fa-exclamation-triangle"></i> Both upload options are disabled because one or more admin or branch users have been approved.';
                    branchSelectionGroup.parentNode.insertBefore(disabledMessage, branchSelectionGroup);
                }
            } else {
                // Only add event listeners if not both disabled
                forecastConsolidated.addEventListener('change', toggleBranchSelection);
                forecastBranch.addEventListener('change', toggleBranchSelection);
            }

            // Initial state
            toggleBranchSelection();
        });
    </script>

</body>

</html>
