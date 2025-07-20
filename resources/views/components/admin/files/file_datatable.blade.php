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
    <style>
        /* Floating button styles */
        #floatingFormatBtn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1050;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        #floatingFormatBtn .fa-eye {
            font-size: 1.5rem;
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
                                    <small class="text-muted">Showing latest 5 files total for current billing period</small>
                                </div>
                                <div class="d-flex align-items-center" style="gap: 10px;">
                                    <a href="{{ route('file.retention.dashboard') }}" class="btn btn-rounded btn-info">
                                        <span class="btn-icon-left text-info">
                                            <i class="fa fa-cogs"></i>
                                        </span>
                                        File Retention
                                    </a>
                                    @if($isApproved)
                                        <button type="button" class="btn btn-rounded btn-primary" data-toggle="modal"
                                            data-target="#exampleModalpopover">
                                            <span class="btn-icon-left text-primary">
                                                <i class="fa fa-upload"></i>
                                            </span>
                                            Upload
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-rounded btn-secondary" disabled
                                            title="File upload is currently disabled because one or more branch users have been approved.">
                                            <span class="btn-icon-left text-secondary">
                                                <i class="fa fa-upload"></i>
                                            </span>
                                            Upload (Disabled)
                                        </button>
                                    @endif
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
                                                @endphp

                                                <div class="form-group">
                                                    <label class="font-weight-bold mb-2">Billing Period</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $billingPeriod }}" readonly>
                                                </div>


                                                <div class="form-group">

                                                    <label for="file" class="font-weight-bold mb-2">📁 Installment
                                                        Forecast
                                                        File
                                                    </label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="file"
                                                            name="file">
                                                        <label class="custom-file-label" for="file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="savings_file" class="font-weight-bold mb-2">💰 Savings
                                                        File</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input"
                                                            id="savings_file" name="savings_file">
                                                        <label class="custom-file-label" for="savings_file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="shares_file" class="font-weight-bold mb-2">📊 Shares
                                                        File</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="shares_file"
                                                            name="shares_file">
                                                        <label class="custom-file-label" for="shares_file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="cif_file" class="font-weight-bold mb-2">👤 CIF File
                                                    </label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="cif_file"
                                                            name="cif_file">
                                                        <label class="custom-file-label" for="cif_file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="loan_file" class="font-weight-bold mb-2">🏛️ Loans
                                                        File
                                                    </label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input"
                                                            id="loan_file" name="loan_file">
                                                        <label class="custom-file-label" for="loan_file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>
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

                                                                                                                @if(!$isApproved)
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <i class="fa fa-exclamation-triangle me-2"></i>
                                    <strong>Upload Disabled:</strong> File upload is currently disabled because one or more branch users have been approved.
                                    Upload is only enabled when all branch users are still in pending status.
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            @endif


                            <div class="card-body">
                                <div class="alert alert-info mb-3">
                                    <i class="fa fa-info-circle me-2"></i>
                                    <strong>Note:</strong> This table shows only the latest 5 files total for the current billing period.
                                    To view all files and manage file retention, use the <strong>File Retention</strong> button above.
                                </div>

                                <div class="alert alert-warning mb-3">
                                    <i class="fa fa-exclamation-triangle me-2"></i>
                                    <strong>Important:</strong> If you're uploading files downloaded from websites and encounter "Invalid Spreadsheet file" errors,
                                    please open the file in Excel and save it as a new .csv file before uploading. This ensures compatibility with our system.
                                </div>
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
                    var fileName = e.target.files.length > 0 ? e.target.files[0].name : 'Choose file...';
                    var label = input.nextElementSibling;
                    if (label && label.classList.contains('custom-file-label')) {
                        label.textContent = fileName;
                    }
                });
            });
        });
    </script>

    <!-- Floating View Format Button -->
    <button id="floatingFormatBtn" class="btn btn-success btn-lg shadow" data-toggle="modal" data-target="#viewFormatModal" title="View File Format Guide">
        <i class="fa fa-eye"></i>
    </button>

    <!-- View Format Modal -->
    <div class="modal fade" id="viewFormatModal" tabindex="-1" role="dialog" aria-labelledby="viewFormatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewFormatModalLabel">File Upload Format Guide</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Below are the required formats for each file type. Please ensure your files match these formats before uploading.</p>
                    <div class="accordion" id="formatAccordion">
                        <div class="card">
                            <div class="card-header" id="headingForecast">
                                <h2 class="mb-0">
                                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapseForecast" aria-expanded="true" aria-controls="collapseForecast">
                                        📁 Installment Forecast File Format
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseForecast" class="collapse show" aria-labelledby="headingForecast" data-parent="#formatAccordion">
                                <div class="card-body">
                                    <strong>Required Columns (Row 5 as header):</strong>
                                    <ul>
                                        <li>cid</li>
                                        <li>branch_code</li>
                                        <li>branch_name</li>
                                        <li>name (Lastname, Firstname)</li>
                                        <li>loan_account_no</li>
                                        <li>open_date</li>
                                        <li>maturity_date</li>
                                        <li>amortization_due_date</li>
                                        <li>total_due</li>
                                        <li>principal_due</li>
                                        <li>interest_due</li>
                                        <li>penalty_due</li>
                                    </ul>
                                    <small class="text-muted">File type: .csv or .xlsx</small>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header" id="headingSavings">
                                <h2 class="mb-0">
                                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseSavings" aria-expanded="false" aria-controls="collapseSavings">
                                        💰 Savings File Format
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseSavings" class="collapse" aria-labelledby="headingSavings" data-parent="#formatAccordion">
                                <div class="card-body">
                                    <strong>Required Columns (Row 6 as header):</strong>
                                    <ul>
                                        <li>customer_no</li>
                                        <li>account_no</li>
                                        <li>product_code</li>
                                        <li>open_date</li>
                                        <li>current_bal</li>
                                        <li>available_bal</li>
                                        <li>interest_due_amount</li>
                                        <li>status</li>
                                        <li>last_trn_date</li>
                                    </ul>
                                    <small class="text-muted">File type: .csv or .xlsx</small>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header" id="headingShares">
                                <h2 class="mb-0">
                                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseShares" aria-expanded="false" aria-controls="collapseShares">
                                        📊 Shares File Format
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseShares" class="collapse" aria-labelledby="headingShares" data-parent="#formatAccordion">
                                <div class="card-body">
                                    <strong>Required Columns (Row 6 as header):</strong>
                                    <ul>
                                        <li>customer_no</li>
                                        <li>account_no</li>
                                        <li>product_code</li>
                                        <li>open_date</li>
                                        <li>current_bal</li>
                                        <li>available_bal</li>
                                        <li>interest_due_amount</li>
                                        <li>status</li>
                                        <li>last_trn_date</li>
                                    </ul>
                                    <small class="text-muted">File type: .csv or .xlsx</small>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header" id="headingCIF">
                                <h2 class="mb-0">
                                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseCIF" aria-expanded="false" aria-controls="collapseCIF">
                                        👤 CIF File Format
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseCIF" class="collapse" aria-labelledby="headingCIF" data-parent="#formatAccordion">
                                <div class="card-body">
                                    <strong>Required Columns (Row 4 as header):</strong>
                                    <ul>
                                        <li>customer_no</li>
                                        <li>customer_name (Lastname, Firstname)</li>
                                        <li>birth_date</li>
                                        <li>date_registered</li>
                                        <li>gender</li>
                                        <li>customer_type</li>
                                        <li>customer_classification</li>
                                        <li>industry</li>
                                        <li>area_officer</li>
                                        <li>area</li>
                                        <li>status</li>
                                        <li>address</li>
                                    </ul>
                                    <small class="text-muted">File type: .csv or .xlsx</small>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header" id="headingLoans">
                                <h2 class="mb-0">
                                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseLoans" aria-expanded="false" aria-controls="collapseLoans">
                                        🏛️ Loans File Format
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseLoans" class="collapse" aria-labelledby="headingLoans" data-parent="#formatAccordion">
                                <div class="card-body">
                                    <strong>Required Columns (Row 1 as header):</strong>
                                    <ul>
                                        <li>CID (Column A)</li>
                                        <li>Account No. (Column B)</li>
                                        <li>Start Date (Column H)</li>
                                        <li>End Date (Column I)</li>
                                        <li>Principal (Column L)</li>
                                    </ul>
                                    <small class="text-muted">File type: .csv or .xlsx</small>
                                    <br><small class="text-muted">Note: Other columns may exist but are not required for import.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <p class="mb-0"><strong>Note:</strong> For best results, always use the provided template or sample file if available. If you have questions, contact your system administrator.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
